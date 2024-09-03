import React from 'react';
import { useTheme } from './ThemenContext';

const Chart = ({ itemId, selectedRowData, type }) => {
    const { theme } = useTheme();
    return (


            <div style={{
                height: '100%',
                display: 'flex',
                padding: '0px 20px 0px 20px',
                backgroundColor: theme === 'light' ? '#ffffff' : '#343a40' // Conditional styling
            }}>
                {selectedRowData ? (
                    <div style={{flex: 1, height: '100%'}}>
                        <div style={{textAlign: 'center'}}><span className="fw-bolder">{selectedRowData.name}_{type} </span>
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

export default Chart;
