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
            padding: '0px 20px 20px 20px',
            backgroundColor: theme === 'light' ? '#ffffff' : '#343a40' // Conditional styling
        }}>
            {selectedRowData ? (
                <div style={{ flex: 1,display:'flex',flexDirection:'column',justifyContent:'space-between' }}>
                    <div style={{ textAlign: 'center' }}><span className="fw-bolder">{selectedRowData.name}</span></div>
                    <div className="panel-box">
                        <Performance selectedRowData={selectedRowData} />
                        <Status selectedRowData={selectedRowData} />
                        <Alert selectedRowData={selectedRowData} />
                        {JSON.parse(selectedRowData.mro).total > 0 && <Mro selectedRowData={selectedRowData} />}
                    </div>
                </div>
            ) : (
                <div className="panel-box" style={{justifyContent: 'center', alignItems: 'center'}}>
                    <span>Loading... <i className="fas fa-cog fa-spin fa-3x"></i></span>
                </div>
            )}
        </div>
    );
};

export default Panel;
