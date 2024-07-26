import React from 'react';
import { PieChart, Pie, Cell, Tooltip, Label } from 'recharts';
import { useNavigate } from 'react-router-dom';


const statusColors = {
    new: '#5cb85c',
    work: '#1779ba',
    wait: '#dca3fc',
    closed: '#f0ad4e'
};

const displayNames = {
    work: 'Work in Process',
    wait: 'Wait External'
};

const CustomTooltip = ({ active, payload }) => {
    if (active && payload && payload.length) {
        const name = displayNames[payload[0].name] || payload[0].name;
        return (
            <div style={{ backgroundColor: '#fff', padding: '2px', border: '1px solid #ccc' }}>
                <p className="label">{`${payload[0].value} ${name} alert(s) the last 7 days`}</p>
            </div>
        );
    }

    return null;
};

const CustomLegend = () => {
    const legendData = [
        { name: 'New', color: statusColors.new },
        { name: 'Work in Process', color: statusColors.work },
        { name: 'Wait External', color: statusColors.wait },
        { name: 'Closed', color: statusColors.closed }
    ];

    return (
        <div style={{ marginLeft: '20px' }}>
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
                                   cx, cy, midAngle, innerRadius, outerRadius, percent, index, payload
                               }) => {
    const RADIAN = Math.PI / 180;
    const radius = innerRadius + (outerRadius - innerRadius) * 0.5;
    const x = cx + radius * Math.cos(-midAngle * RADIAN);
    const y = cy + radius * Math.sin(-midAngle * RADIAN);

    return (
        <text x={x} y={y} fill="white" textAnchor="middle" dominantBaseline="central" style={{ fontSize: '0.75rem' }}>
            {payload.value}
        </text>
    );
};

const Alert = ({ selectedRowData }) => {
    const navigate = useNavigate();

    if (!selectedRowData) {
        return <span>Loading... <i className="fas fa-cog fa-spin fa-3x"></i></span>;
    }

    const statusData = JSON.parse(selectedRowData.last_7_days_tickets);
    const status = [
        { name: 'new', value: statusData.status_10 },
        { name: 'work', value: statusData.status_30 },
        { name: 'wait', value: statusData.status_40 },
        { name: 'closed', value: statusData.status_90 }
    ];
    const filteredStatus = status.filter(s => s.value > 0);

    return (
        <div className="panel-box">
            <div style={{
                display: 'flex',
                flexDirection: 'column',
                justifyContent: 'space-around'
            }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <h3>Alerts <span className="panel-white">Last 7 days</span></h3>
                    <button
                        onMouseDown={(e) => e.stopPropagation()}
                        onClick={(e) => {
                            e.stopPropagation();
                            navigate(`/new/alerts/${selectedRowData.id}`);
                        }}

                    >
                        <i className="fa fa-chevron-right"></i>
                    </button>
                </div>
                <div style={{display: 'flex', justifyContent: 'space-between' }}>
                    <PieChart width={200} height={200}>
                        <Pie
                            data={filteredStatus}
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
                                style={{ fontSize: '0.9rem', fill: 'white' }}
                            />
                            {filteredStatus.map((entry, index) => (
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
