import React from 'react';

const Chart = ({ itemId, selectedRowData }) => {
    return (
        <div>
            <h3>Chart Component</h3>
            {selectedRowData && <p>Selected Row ID: {selectedRowData.id}</p>}
            {selectedRowData && <pre>{JSON.stringify(selectedRowData, null, 2)}</pre>}
        </div>
    );
};

export default Chart;
