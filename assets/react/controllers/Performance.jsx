import React from 'react';
import { useNavigate } from 'react-router-dom';

const Performance = ({ selectedRowData }) => {
    const navigate = useNavigate();
    if (!selectedRowData) {
        return <span>Loading... <i className="fas fa-cog fa-spin fa-3x"></i></span>;
    }

    const pr_act = JSON.parse(selectedRowData.pr_act || '{}');
    const pr_exp = JSON.parse(selectedRowData.pr_exp || '{}');
    const pr_yesterday = JSON.parse(selectedRowData.pr_yesterday || '{}');
    const pr_year = JSON.parse(selectedRowData.pr_year || '{}');
    const pnom = selectedRowData.pnom;

    return (
        <div className="panel-box">
            <div className="panel-box-container" >
                <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center'}}>
                    <h3>Performance</h3>
                    <button
                        onMouseDown={(e) => e.stopPropagation()}
                        onClick={(e) => {
                            e.stopPropagation();
                            navigate(`/new/performance/${selectedRowData.id}`);
                        }}
                        className="btn"
                    >
                        <i className="fa fa-chevron-right" ></i>
                    </button>
                </div>
                <div className="panel-pr">
                    <div style={{paddingRight: '10px'}}>
                        <div>{pr_act.acActAll || '0.00'} <span className="panel-white">KWh</span></div>
                        <div className="panel-white">Performance at <span
                           className="panel-white-io">{pr_act.lastDataIo}</span></div>
                    </div>
                    <div>
                        <div>{pr_exp.acExpAll || '0.00'} <span className="panel-white">GWh</span></div>
                        <div className="panel-white">Expected performance</div>
                    </div>
                </div>
                <div className="panel-pr">
                    <div style={{paddingRight: '10px'}}>
                        <div>{pr_yesterday.prYesterday || '0.00'} <span className="panel-white">KWh</span></div>
                        <div className="panel-white">Performance yesterday</div>
                    </div>
                    <div>
                        <div>{pr_yesterday.prYesterdayExp || '0.00'} <span className="panel-white">GWh</span></div>
                        <div className="panel-white">Expected performance</div>
                    </div>
                </div>
                <div className="panel-pr">
                    <div style={{paddingRight: '10px'}}>
                        <div>{pnom || '0.00'} <span className="panel-white">KWp</span></div>
                        <div className="panel-white">Total strings capacity</div>
                    </div>
                    <div>
                        <div>{pr_year.power || '0.00'} <span className="panel-white">KWh</span></div>
                        <div className="panel-white">Annual total energy yield</div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Performance;
