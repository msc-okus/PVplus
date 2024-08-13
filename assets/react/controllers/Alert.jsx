import React from 'react';
import { PieChart, Pie, Cell, Tooltip, Label } from 'recharts';
import { useNavigate } from 'react-router-dom';
import { useTheme } from './ThemenContext'; // Import the useTheme hook

const Alert = ({ selectedRowData }) => {
    const navigate = useNavigate();
    const { theme } = useTheme(); // Use the theme from context

    const statusColors = {
        new: '#f0ad4e',
        work: '#1779ba',
        wait: '#dca3fc',
        closed: '#5cb85c',
        empty: theme === 'light' ? '#f3f5f5' : '#ffffff' // Apply conditional styling
    };

    const CustomTooltip = ({ active, payload }) => {
        if (active && payload && payload.length) {
            const isEmpty = payload[0].name === 'empty';

            return (
                isEmpty ? (
                    <span className="badge bg-secondary">{`0  alert`}</span>
                ) : (
                    <div style={{
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        padding: '10px',
                        border: '1px solid #ccc',
                        borderRadius: '4px',
                        boxShadow: '0 0 10px rgba(0, 0, 0, 0.1)',
                        color: '#333',
                        fontSize: '0.9rem'
                    }}>
                        <p className="text-center"><span
                            className="badge bg-secondary">{`${payload[0].value} ${payload[0].payload.description} alert(s)`}</span>
                        </p>
                        <div className="d-flex flex-wrap">
                            {payload[0].payload.i.split(',').slice(0, 23).map((value, index) => (
                                <span key={index} className="fw-bold px-1">{value.trim()}</span>
                            ))}
                            {payload[0].payload.i.split(',').length > 24 && (
                                <span className="fw-bold px-1">...</span>
                            )}
                        </div>

                    </div>
                )
            );
        }

        return null;
    };

    const CustomLegend = () => {
        const legendData = [
            {name: 'New', color: statusColors.new},
            {name: 'Work in process', color: statusColors.work},
            {name: 'Wait external', color: statusColors.wait},
            {name: 'Closed', color: statusColors.closed},
            {name: 'No alert', color: statusColors.empty}
        ];

        return (
            <div style={{marginLeft: '20px'}}>
                {legendData.map((entry, index) => (
                    <div key={index} style={{ display: 'flex', alignItems: 'center', marginBottom: '4px' }}>
                        <span style={{
                            display: 'inline-block',
                            width: '12px',
                            height: '12px',
                            backgroundColor: entry.color,
                            marginRight: '6px'
                        }}></span>
                        {entry.name}
                    </div>
                ))}
            </div>
        );
    };

    const renderCustomizedLabel = ({
                                       cx, cy, midAngle, innerRadius, outerRadius, index, payload
                                   }) => {
        const RADIAN = Math.PI / 180;
        const radius = innerRadius + (outerRadius - innerRadius) * 0.5;
        const x = cx + radius * Math.cos(-midAngle * RADIAN);
        const y = cy + radius * Math.sin(-midAngle * RADIAN);
        const value = payload.name === 'empty' ? '' : payload.value;

        return (
            <text x={x} y={y} fill="white" textAnchor="middle" dominantBaseline="central" style={{ fontSize: '0.75rem' }}>
                {value}
            </text>
        );
    };


    if (!selectedRowData) {
        return <span>Loading... <i className="fas fa-cog fa-spin fa-3x"></i></span>;
    }

    const statusData = JSON.parse(selectedRowData.last_7_days_tickets);
    const status = [
        { name: 'new', value: statusData.status_10.s, i: statusData.status_10.alerts, description: 'New'  },
        { name: 'work', value: statusData.status_30.s, i: statusData.status_30.alerts, description: 'Work in process' },
        { name: 'wait', value: statusData.status_40.s, i: statusData.status_40.alerts, description: 'Wait external' },
        { name: 'closed', value: statusData.status_90.s, i: statusData.status_90.alerts, description: 'Closed' }
    ];
    const filteredStatus = status.filter(s => s.value > 0);

    const pieData = filteredStatus.length > 0 ? filteredStatus : [{ name: 'empty', value: 1 }];

    return (
        <div className="panel-box">
            <div className="panel-box-container">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <h3>Alerts <span className="panel-white">Last 7 days</span></h3>
                    <button
                        onMouseDown={(e) => e.stopPropagation()}
                        onClick={(e) => {
                            e.stopPropagation();
                            navigate(`/new/alerts/${selectedRowData.id}`);
                        }}
                        className="btn"
                    >
                        <i className="fa fa-chevron-right"></i>
                    </button>
                </div>
                <div style={{display: 'flex', justifyContent: 'space-between' }}>
                    <PieChart width={200} height={200}>
                        <Pie
                            data={pieData}
                            dataKey="value"
                            nameKey="name"
                            cx="50%"
                            cy="50%"
                            outerRadius={100}
                            innerRadius={50}
                            fill="#8884d8"
                            labelLine={false}
                            label={renderCustomizedLabel}
                        >
                            <Label
                                value={`Total ${statusData.total}`}
                                position="center"
                                style={{ fontSize: '0.9rem', fill: theme==='light'?'black':'white' }}
                            />
                            {pieData.map((entry, index) => (
                                <Cell key={`cell-${index}`} fill={statusColors[entry.name]} />
                            ))}
                        </Pie>
                        <Tooltip content={<CustomTooltip />} />
                    </PieChart>
                    <CustomLegend />
                </div>
            </div>
        </div>
    );
};

export default Alert;
