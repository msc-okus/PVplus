<?php

namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Closure;
use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Types\Types as DBALType;
use Doctrine\ORM\QueryBuilder;

final class JsonFilter extends AbstractContextAwareFilter implements JsonFilterInterface
{
    public const DOCTRINE_JSON_TYPES = [
        DBALType::JSON => true,
    ];

    /**
     * Get swagger documentation field description.
     */
    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        $description = [];

        foreach ($this->properties as $property => $config) {
            // property infos
            $propertyName = $property;

            $propertyType = $this->getPropertyType($property);

            $propertyStrategy = $this->getPropertyStrategy($property);

            $isPropertyRequired = $this->isPropertyRequired($config);

            // forge filters
            $filterParameterNames = [$propertyName];

            $filterParameterNames = $this->addPropertyNameToFilter(
                $filterParameterNames,
                $propertyType,
                $propertyName,
                $propertyStrategy
            );

            foreach ($filterParameterNames as $filterParameterName) {
                $description[$filterParameterName] = [
                    'property' => $propertyName,
                    'type' => $propertyType,
                    'strategy' => $propertyStrategy,
                    'required' => $isPropertyRequired,
                    'is_collection' => str_ends_with((string) $filterParameterName, '[]'),
                ];
            }
        }

        return $description;
    }

    //
    // PROTECTED FUNCTIONS
    //

    /**
     * Filter property.
     *
     * @param $value
     *
     * @return void
     */
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ) {
        $jsonColumn = $this->getJsonColumn($property);

        // check - standard checks
        // check - is valid type
        // check - is valid value
        if (!$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($jsonColumn, $resourceClass) ||
            !$this->isJsonField($jsonColumn, $resourceClass) ||
            !$this->isValidType($property) ||
            null === $value
        ) {
            return;
        }

        $values = $this->normalizeValues((array) $value, $property);

        // manage filters per type
        $type = $this->getPropertyType($property);

        match ($type) {
            self::TYPE_STRING => $this->addStringFilter($property, $values, $queryBuilder, $queryNameGenerator),
            self::TYPE_INT, self::TYPE_FLOAT => $this->addNumericFilter($property, $values, $queryBuilder, $queryNameGenerator),
            self::TYPE_BOOLEAN => $this->addBooleanFilter($property, $values, $queryBuilder, $queryNameGenerator),
            default => throw new InvalidArgumentException("Type \"{$type}\" specified for property \"{$property}\" is not supported (yet)."),
        };
    }

    /**
     * Check & normalize value.
     *
     * @return mixed
     */
    protected function normalizeValues(array $values, string $property)
    {
        if (empty($values)) {
            $this->setLoggerError(
                'Invalid filter ignored',
                'At least one value is required, multiple values should be in '
                .'"%1$s[]=firstvalue&%1$s[]=secondvalue" format',
                [$property]
            );
        }

        $propertyType = $this->getPropertyType($property);

        return match ($propertyType) {
            self::TYPE_INT, self::TYPE_FLOAT => !$this->isNumericValue($values, $property) ? [] : array_values($values),
            self::TYPE_BOOLEAN => $this->getBooleanValue($values, $property),
            default => array_values($values),
        };
    }

    //
    // PRIVATE FUNCTIONS
    //

    private function isNumericValue(array $values, string $property): bool
    {
        if (!is_numeric($values) && (!is_array($values) || !$this->isNumericArray($values))) {
            $this->setLoggerError(
                'Invalid filter ignored',
                'Invalid numeric value for "%s" property',
                [$property]
            );

            return false;
        }

        return true;
    }

    private function getBooleanValue(array $values, string $property): ?bool
    {
        if (in_array($values[0], [true, 'true', '1'], true)) {
            return true;
        }

        if (in_array($values[0], [false, 'false', '0'], true)) {
            return false;
        }

        $incites = implode('" | "', [
            'true',
            'false',
            '1',
            '0',
        ]);

        $this->setLoggerError(
            'Invalid filter ignored',
            'Invalid boolean value for "%s" property, expected one of ( "%s" )',
            [$property, $incites]
        );

        return null;
    }

    /**
     * Checked if a property is required.
     */
    private function isPropertyRequired(mixed $config): bool
    {
        return isset($config['required']) ? filter_var($config['required'], FILTER_VALIDATE_BOOLEAN) : false;
    }

    /**
     * Is JSON Field.
     */
    private function isJsonField(string $property, string $resourceClass): bool
    {
        return isset(self::DOCTRINE_JSON_TYPES[(string) $this->getDoctrineFieldType($property, $resourceClass)]);
    }

    /**
     * Is valid / available type.
     *
     * @return bool
     */
    private function isValidType(string $property): ?bool
    {
        $propertyType = $this->getPropertyType($property);

        // is available type ?
        if (isset(self::AVAILABLE_TYPES[$propertyType])) {
            return true;
        }

        $this->setLoggerError(
            'Invalid filter type',
            'Invalid filter type (%s) specified for "%s" property',
            [$propertyType, $property]
        );

        return false;
    }

    /**
     * Is numeric array.
     */
    private function isNumericArray(array $values): bool
    {
        foreach ($values as $value) {
            if (!is_numeric($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get JSON Column.
     *
     * @return string
     */
    private function getJsonColumn(string $property)
    {
        $jsonColumnInfos = explode('.', $property);

        return reset($jsonColumnInfos);
    }

    /**
     * Get JSON Key.
     *
     * @return string
     */
    private function getJsonKey(string $property)
    {
        $jsonColumnInfos = explode('.', $property);
        array_shift($jsonColumnInfos);

        $jsonKeys = implode('.', $jsonColumnInfos);

        // return keys separated by . notation and in snake_case (it's received in camelCase)
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $jsonKeys));
    }

    /**
     * Get property type.
     *
     * @return string
     */
    private function getPropertyType(string $property)
    {
        $properties = $this->getProperties();

        if (!array_key_exists($property, $properties)) {
            return self::TYPE_STRING;
        }

        $propertyConfig = $properties[$property];

        return $propertyConfig['type'] ?? self::TYPE_STRING;
    }

    /**
     * Get property strategy.
     *
     * @return string
     */
    private function getPropertyStrategy(string $property)
    {
        $propertyConfig = $this->getProperties()[$property];

        return $propertyConfig['strategy'] ?? self::STRATEGY_PARTIAL;
    }

    /**
     * Add property name to array of filters.
     */
    private function addPropertyNameToFilter(
        array $filterParameterNames,
        string $propertyType,
        string $propertyName,
        string $propertyStrategy
    ): array {
        if ($propertyType == self::TYPE_INT
            || $propertyType == self::TYPE_FLOAT
            || ($propertyType == self::TYPE_STRING && self::STRATEGY_EXACT === $propertyStrategy)) {
            $filterParameterNames[] = $propertyName.'[]';
        }

        return $filterParameterNames;
    }

    /**
     * Add numeric filter.
     *
     * @return void
     */
    private function addNumericFilter(
        string $property,
        array $values,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator
    ) {
        $alias = $queryBuilder->getRootAliases()[0];
        $jsonColumn = $this->getJsonColumn($property);
        $jsonKey = $this->getJsonKey($property);
        $propertyType = $this->getPropertyType($property);

        if (1 === count($values)) {
            $valueParameter = $queryNameGenerator->generateParameterName($jsonColumn);

            $queryBuilder
                ->andWhere("JSON_UNQUOTE(JSON_EXTRACT({$alias}.{$jsonColumn}, '$.{$jsonKey}')) = :{$valueParameter}")
                ->setParameter(
                    $valueParameter,
                    $values[0],
                    $propertyType == self::TYPE_FLOAT ? DBALType::FLOAT : DBALType::INTEGER
                );
        } else {
            $condition = implode(' OR ', array_map(fn($value) => "JSON_UNQUOTE(JSON_EXTRACT({$alias}.{$jsonColumn}, '$.{$jsonKey}')) = {$value}", $values));

            $queryBuilder
                ->andWhere("({$condition})");
        }
    }

    /**
     * Add boolean filter.
     *
     * @param bool $value
     *
     * @return void
     */
    private function addBooleanFilter(
        string $property,
        ?bool $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator
    ) {
        $alias = $queryBuilder->getRootAliases()[0];
        $jsonColumn = $this->getJsonColumn($property);
        $jsonKey = $this->getJsonKey($property);
        $valueParameter = $queryNameGenerator->generateParameterName($jsonColumn);

        // TODO : find a way to compare to raw boolean value
        // for the moment boolean values are replace by 0 and 1 by doctrine
        // right way to do it:
        // $condition = "JSON_EXTRACT({$alias}.{$jsonColumn}, '$.{$jsonKey}') = :{$valueParameter}";
        // workaround: comparing string values of true & false instead of boolean type
        $condition = "JSON_UNQUOTE(JSON_EXTRACT({$alias}.{$jsonColumn}, '$.{$jsonKey}')) = :{$valueParameter}";

        if ($value === false) {
            $condition .= " OR JSON_EXTRACT({$alias}.{$jsonColumn}, '$.{$jsonKey}') IS NULL";
        }

        $queryBuilder
            ->andWhere("({$condition})")
            ->setParameter($valueParameter, $value ? 'true' : 'false');
    }

    /**
     * Add string filter depending on the strategy.
     *
     * @return void
     */
    private function addStringFilter(
        string $property,
        array $values,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator
    ) {
        $strategy = $this->getPropertyStrategy($property);
        $caseSensitive = true;

        // prefixing the strategy with i makes it case insensitive
        if (str_starts_with($strategy, 'i')) {
            $strategy = substr($strategy, 1);
            $caseSensitive = false;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $jsonColumn = $this->getJsonColumn($property);
        $jsonKey = $this->getJsonKey($property);

        if (1 === count($values)) {
            $this->addStringFilterWhereByStrategy(
                $strategy,
                $queryBuilder,
                $queryNameGenerator,
                $alias,
                $jsonColumn,
                $jsonKey,
                $values[0],
                $caseSensitive
            );

            return;
        }

        if (self::STRATEGY_EXACT !== $strategy) {
            $this->setLoggerError(
                'Invalid filter ignored',
                '"%s" strategy selected for "%s" property, but only "%s" strategy supports multiple values',
                [$strategy, $property]
            );

            return;
        }

        // manage strategy exact with multiple values
        $wrapCase = $this->createWrapCase($caseSensitive);
        $jsonColumnWithAlias = $wrapCase("{$alias}.{$jsonColumn}");

        $condition = '';

        // forge condition
        foreach ($values as $index => $value) {
            $condition = $this->addOperatorToQueryCondition($condition, $index);

            $valueParameter = $queryNameGenerator->generateParameterName($jsonColumn.$index);
            $condition .= "JSON_UNQUOTE(JSON_EXTRACT({$jsonColumnWithAlias}, '$.{$jsonKey}')) = :{$valueParameter}";

            $value = $caseSensitive ? $value : strtolower((string) $value);

            $queryBuilder->setParameter($valueParameter, $value, Types::STRING);
        }

        $queryBuilder->andWhere("({$condition})");
    }

    private function addOperatorToQueryCondition(string $condition, int $index)
    {
        $condition = $index > 0 ? ' OR ' : $condition;

        return $condition;
    }

    /**
     * Adds string filter where clause according to the strategy.
     *
     * @param $value
     */
    private function addStringFilterWhereByStrategy(
        string $strategy,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $alias,
        string $jsonColumn,
        string $jsonKey,
        $value,
        bool $caseSensitive
    ) {
        $wrapCase = $this->createWrapCase($caseSensitive);
        $valueParameter = $queryNameGenerator->generateParameterName($jsonColumn);
        $jsonColumnWithAlias = $wrapCase("{$alias}.{$jsonColumn}");

        switch ($strategy) {
            case null:
            case self::STRATEGY_EXACT:
                $queryBuilder
                    ->andWhere(
                        "JSON_UNQUOTE(JSON_EXTRACT({$jsonColumnWithAlias}, '$.{$jsonKey}')) = :{$valueParameter}"
                    )
                    ->setParameter($valueParameter, $value, Types::STRING);

                break;

            case self::STRATEGY_PARTIAL:
                $queryBuilder
                    ->andWhere(
                        "JSON_UNQUOTE(JSON_EXTRACT({$jsonColumnWithAlias}, '$.{$jsonKey}')) LIKE "
                        .$wrapCase("CONCAT('%%', :{$valueParameter}, '%%')")
                    )
                    ->setParameter($valueParameter, $value, Types::STRING);

                break;

            case self::STRATEGY_START:
                $queryBuilder
                    ->andWhere(
                        "JSON_UNQUOTE(JSON_EXTRACT({$jsonColumnWithAlias}, '$.{$jsonKey}')) LIKE "
                        .$wrapCase("CONCAT(:{$valueParameter}, '%%')")
                    )
                    ->setParameter($valueParameter, $value, Types::STRING);

                break;

            case self::STRATEGY_END:
                $queryBuilder
                    ->andWhere(
                        "JSON_UNQUOTE(JSON_EXTRACT({$jsonColumnWithAlias}, '$.{$jsonKey}')) LIKE "
                        .$wrapCase("CONCAT('%%', :{$valueParameter}")
                    )
                    ->setParameter($valueParameter, $value, Types::STRING);

                break;

            case self::STRATEGY_WORD_START:
                $queryBuilder
                    ->andWhere(
                        '('
                        ."JSON_UNQUOTE(JSON_EXTRACT({$jsonColumnWithAlias}, '$.{$jsonKey}')) LIKE "
                        .$wrapCase("CONCAT(:{$valueParameter}, '%%')")
                        .' OR '
                        ."JSON_UNQUOTE(JSON_EXTRACT({$jsonColumnWithAlias}, '$.{$jsonKey}')) LIKE "
                        .$wrapCase("CONCAT('%% ', :{$valueParameter}, '%%')")
                        .')'
                    )
                    ->setParameter($valueParameter, $value, Types::STRING);

                break;

            default:
                throw new InvalidArgumentException("strategy {$strategy} does not exist.");
        }
    }

    /**
     * Creates a function that will wrap a Doctrine expression according to the
     * specified case sensitivity.
     *
     * For example, "o.name" will get wrapped into "LOWER(o.name)" when $caseSensitive
     * is false.
     */
    private function createWrapCase(bool $caseSensitive): Closure
    {
        return static function (string $expr) use ($caseSensitive): string {
            if ($caseSensitive) {
                return $expr;
            }

            return sprintf('LOWER(%s)', $expr);
        };
    }

    private function setLoggerError(string $type, string $message, array $properties)
    {
        $message = vsprintf($message, $properties);
        $exception = new InvalidArgumentException($message);

        $this->getLogger()->error($type, [
            'exception' => $exception,
        ]);

        throw $exception;
    }
}
