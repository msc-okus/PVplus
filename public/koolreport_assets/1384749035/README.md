# Chart Table

`koolreport/charttable` is package to show in both chart and table together.

## Installation

1. Download and unzip the zipped file.
2. Copy the folder `charttable` into `koolreport` folder
3. Reference to the ChartTable widget by the classname`\koolreport\charttable\ChartTable`

## Requirement

To use the `ChartTable` widget, beside ChartTable package you need the `Inputs`, `DataGrid` and `Chartjs` packages as well.

## Usage

### ChartTable

To use `ChartTable` you only need to define its `dataSource` property.

```
\koolreport\charttable\ChartTable::create(array(
    "name" => "charttable1",
    "dataSource" => $this->dataStore('myDatastore')
));
```

#### Properties

|name|type|default|description|
|---|---|---|---|
|`name`|string||Optional. You can set the name for your charttable if you want to refer to the table later on at client-side. If you don't set we will set random name for table|
|`dataSource`|DataStore|| Specify a dataStore, an array or a function that data table will get data from|
|`columns`|Array||Optional. Set charttable's columns instead of using its datastore's columns|
|`options`|Array||Optional. Set charttable's DataTables object's options |

## Support

Please use our forum if you need support, by this way other people can benefit as well. If the support request need privacy, you may send email to us at __support@koolreport.com__.