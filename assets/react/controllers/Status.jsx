import React from 'react';
import { PieChart, Pie, Cell, Tooltip, Label } from 'recharts';
import {useNavigate} from "react-router-dom";
import {useTheme} from "./ThemenContext";

const statusColors = {
    warning: '#f0ad4e',
    alert: '#d9534f',
    normal: '#5cb85c',
    null: '#1779ba'
};

const displayNames = {
    plant: 'IO Plant',
    Weather: 'IO Weather',
    AcDiff: 'Ac Diff',
    DcDiff: 'Dc Diff',
    Inv: 'Inverter'
};

const CustomTooltip = ({ active, payload }) => {
    if (active && payload && payload.length) {
        const name = displayNames[payload[0].name] || payload[0].name;
        return (

            <span className="badge bg-secondary">{`${name}`}</span>

        );
    }

    return null;
};

const CustomLegend = () => {
    const legendData = [
        { name: 'Warning', color: statusColors.warning },
        { name: 'Alert', color: statusColors.alert },
        { name: 'Normal', color: statusColors.normal },
        { name: 'No data', color: statusColors.null }
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
                                   cx, cy, midAngle, innerRadius, outerRadius, percent, index, name
                               }) => {
    const RADIAN = Math.PI / 180;
    const radius = innerRadius + (outerRadius - innerRadius) * 0.5;
    const x = cx + radius * Math.cos(-midAngle * RADIAN);
    const y = cy + radius * Math.sin(-midAngle * RADIAN);

    return (
        <text x={x} y={y} fill="white" textAnchor="middle" dominantBaseline="central" style={{ fontSize: '0.75rem' }}>
            {name}
        </text>
    );
};

const Status = ({ selectedRowData }) => {
    const navigate = useNavigate();
    const { theme } = useTheme();
    if (!selectedRowData) {
        return <span>Loading... <i className="fas fa-cog fa-spin fa-3x"></i></span>;
    }

    const statusData = JSON.parse(selectedRowData.statusData);
    const pr_act = JSON.parse(selectedRowData.pr_act || '{}');


    const data = [
        { name: 'plant', value: 1, status: statusData.ioPlantStatus || 'null' },
        { name: 'Weather', value: 1, status: statusData.ioWeatherStatus || 'null' },
        { name: 'AcDiff', value: 1, status: statusData.acDiffStatus || 'null' },
        { name: 'DcDiff', value: 1, status: statusData.dcDiffStatus || 'null' },
        { name: 'Inv', value: 1, status: statusData.invStatus || 'null' },
        { name: 'Strings', value: 1, status: statusData.dcStatus || 'null' },
    ];

    return (
        <div className="panel-box">
            <div className="panel-box-container">
                <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center'}}>
                    <h3>Status <span className="panel-white">Today</span></h3>
                    <button
                        onMouseDown={(e) => e.stopPropagation()}
                        onClick={(e) => {
                            e.stopPropagation();
                            navigate(`/new/status/${selectedRowData.id}`);
                        }}
                        className="btn"

                    >
                        <i className="fa fa-chevron-right" ></i>
                    </button>
                </div>
                <div style={{display: 'flex', justifyContent: 'space-between'}}>
                    <PieChart width={200} height={200}>
                        <Pie
                            data={data}
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
                                value={`Hour ${pr_act.lastDataIo}`}
                                position="center"
                                style={{ fontSize: '0.9rem', fill: theme === 'light' ? 'black' : 'white' }}
                            />
                            {data.map((entry, index) => (
                                <Cell key={`cell-${index}`} fill={statusColors[entry.status]}/>
                            ))}
                        </Pie>
                        <Tooltip content={<CustomTooltip/>}/>
                    </PieChart>
                    <CustomLegend/>
                </div>
            </div>
        </div>
    );
};

export default Status;
