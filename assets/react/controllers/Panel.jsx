import React from 'react';
import Performance from './Performance';
import Status from './Status';
import Alert from './Alert';

const Panel = ({ itemId, selectedRowData }) => {
    return (
        <div style={{
            height:'100%',
            display: 'flex',
            padding: '5px 20px 20px 10px',
            backgroundColor: '#002d72'
        }}>
            {selectedRowData?(
                <div style={{flex:1}}>
                    <div style={{textAlign:'center'}}><span className="panel-white">{selectedRowData.name}</span></div>
                    <div className="panel-box">
                         <Performance selectedRowData={selectedRowData} />
                         <Status selectedRowData={selectedRowData} />
                         <Alert selectedRowData={selectedRowData} />

                    </div>
                </div>
            ): (
                <div className="panel-box" style={{ justifyContent:'center',alignItems:'center' }}>
                    <h3>No Plant Selected</h3>
                </div>
            )
            }


        </div>
    );
};

export default Panel;
