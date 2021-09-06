<?php
use koolreport\excel\Table;

$headlines = $this->dataStore('headlines')->toArray()[0];
?>
<meta charset="UTF-8">
<meta name="description" content="Free Web tutorials">
<meta name="keywords" content="Excel,HTML,CSS,XML,JavaScript">
<meta name="creator" content="green4net">
<meta name="subject" content="subject1">
<meta name="title" content="title1">

<div sheet-name="Basic Values">
    <div>Basic Values</div>
    <div>
        <?php
        //
        Table::create([
            'dataSource'    => $this->dataStore('header'),
        ]);
        ?>
    </div>
</div>
<div sheet-name="PR">
    <div>PR</div>
    <div>
        <?php
        // PR
        Table::create([
            'dataSource'    => $this->dataStore('forecast'),
            'columns'       => [
                'PRDiffYear'  => [
                    'label'     => 'PR prog_year - PR guar_year [%]',
                ],
                'message'   => [
                    'label'     => '',
                ],
                'pld'       => [
                    'label'     => 'Total PLD [EUR]',
                ]
            ]
        ]);
        ?>
    </div>
</div>
<div sheet-name="PLD">
    <div>PLD</div>
    <div>
        <?php
        //PLD
        Table::create([
            'dataSource'    => $this->dataStore('pld'),
        ]);
        ?>
    </div>
</div>
<div sheet-name="Monthly Values">
    <div>Monthly Values</div>
    <div>
        <?php
        //Monthly Values
        Table::create([
            'dataSource'    => $this->dataStore('main'),
        ]);
        ?>
    </div>
</div>
