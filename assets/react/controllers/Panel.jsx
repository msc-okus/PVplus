import React from 'react';
import Performance from './Performance';
import Status from './Status';
import Alert from './Alert';
import { useTheme } from './ThemenContext';
import Mro from "./Mro"; // Import the useTheme hook

const Panel = ({ itemId, selectedRowData }) => {
    const { theme } = useTheme(); // Use the theme from context

    return (
        <div style={{
            height: '100%',
            display: 'flex',
            padding: '0px 20px 0px 20px',
            backgroundColor: theme === 'light' ? '#ffffff' : '#343a40' // Conditional styling
        }}>
            {selectedRowData ? (
                <div style={{ flex: 1,height:'100%' }}>
                    <div style={{ textAlign: 'center' }}><span className="panel-white">{selectedRowData.name}</span></div>
                    <div className="panel-box">
                        <Performance selectedRowData={selectedRowData} />
                        <Status selectedRowData={selectedRowData} />
                        <Alert selectedRowData={selectedRowData} />
                        {JSON.parse(selectedRowData.mro).total > 0 && <Mro selectedRowData={selectedRowData} />}
                    </div>
                </div>
            ) : (
                <div className="panel-box" style={{ justifyContent: 'center', alignItems: 'center' }}>
                    <h3>No Plant Selected</h3>
                </div>
            )}
        </div>
    );
};

export default Panel;
