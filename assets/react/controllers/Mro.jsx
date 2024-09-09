import React from 'react';
import { PieChart, Pie, Cell, Tooltip, Label } from 'recharts';
import { useNavigate } from 'react-router-dom';
import { useTheme } from './ThemenContext'; // Import the useTheme hook

const Mro = ({ selectedRowData }) => {
    const navigate = useNavigate();
    const { theme } = useTheme(); // Use the theme from context

    const statusColors = {
        new: '#f0ad4e',
        work: '#1779ba',
        closed: '#5cb85c'
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
                            className="badge bg-secondary">{`${payload[0].value} ${payload[0].payload.description} mro(s)`}</span>
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
            {name: 'Pending processing', color: statusColors.new},
            {name: 'processing', color: statusColors.work},
            {name: 'processing completed', color: statusColors.closed}
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
        const value =  payload.value;

        return (
            <text x={x} y={y} fill="white" textAnchor="middle" dominantBaseline="central" style={{ fontSize: '0.75rem' }}>
                {value}
            </text>
        );
    };


    if (!selectedRowData) {
        return <span>Loading... <i className="fas fa-cog fa-spin fa-3x"></i></span>;
    }

    const statusData = JSON.parse(selectedRowData.mro);
    const status = [
        { name: 'new', value: statusData.new.zahl, i: statusData.new.alerts, description: 'Pending processing'  },
        { name: 'work', value: statusData.work.zahl, i: statusData.work.alerts, description: 'processing' },
        { name: 'closed', value: statusData.closed.zahl, i: statusData.closed.alerts, description: 'processing completed' }
    ];




    return (
        <div className="panel-box">
            <div className="panel-box-container">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <h3>Mros</h3>
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
                            data={status}
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
                            {status.map((entry, index) => (
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

export default Mro;
