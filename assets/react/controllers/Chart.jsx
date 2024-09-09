import React, { useState, useEffect } from 'react';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';
import axios from 'axios';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer,Area } from 'recharts';
import { useTheme } from './ThemenContext';
import CustomSwitch from './CustomSwitch';

const Chart = ({ itemId, selectedRowData, type }) => {
    const { theme } = useTheme();
    const [startDate, setStartDate] = useState(new Date());
    const [endDate, setEndDate] = useState(new Date());
    const [chartData, setChartData] = useState([]);
    const [sumData, setSumData] = useState([]);
    const [lines, setLines] = useState([]);
    const [hiddenLines, setHiddenLines] = useState({
        p_set_gridop_rel: true, // Hidden by default
        p_set_rpc_rel: true,
        InvOut: true,
        theoPower: true,
        expexted_no_limit:true,
        expgood:true,
        expected:true

    }); // State to track hidden lines
    const [form, setForm] = useState({
        anlageId: selectedRowData?.id || '',
        selectedChart: type,
        toggleOption: false,
    });

    const maxDate = new Date(); // Current date as the maximum selectable date

    // Key-color mapping
    const keyColorMap = {
        expected: '#f3a716',
        expgood: '#f3a716',
        expexted_evu: '#ffc658',
        expexted_evu_good: '#ffc658',
        InvOut: '#8884d8',
        eZEvu: '#82ca9d',
        theoPower: '#525252',
        irradiation: '#773B4D',
        p_set_gridop_rel: '#FF4500',  // Assign specific color if needed
        p_set_rpc_rel: '#32CD32',     // Assign specific color if needed
    };

    const fetchChartData = async () => {
        try {
            const response = await axios.post('/new/chart', {
                ...form,
                startDate: startDate.toISOString(),
                endDate: endDate.toISOString(),
            });

            const data = JSON.parse(response.data[0].data);
            const sum = JSON.parse(response.data[0].sum);
            setChartData(data);
            setSumData(sum);

            const formatNumber = (value) => {
                return value ? value.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
            };

            const customLegendNames = {
                eZEvu: `Grid ${sum?.evuSum ? formatNumber(sum.evuSum) + ' kWh' : ''}`,
                expected: `Expected Inverter Out ${sum?.expSum ? formatNumber(sum.expSum) + ' kWh' : ''}`,
                expgood: `Expected Inverter Out Good `,
                expexted_evu: `Expected Grid  kWh ${sum?.expEvuSum ? formatNumber(sum.expEvuSum) + ' kWh' : ''}`,
                expexted_evu_good: `Expected Grid Good `,
                expexted_no_limit: `Expected (no limit) ${sum?.expNoLimitSum ? formatNumber(sum.expNoLimitSum) + ' kWh' : ''}`,
                InvOut: `Inverter Out ${sum?.actSum ? formatNumber(sum.actSum) + ' kWh' : ''}`,
                theoPower: `Theoretical Power ${sum?.theoPowerSum ? formatNumber(sum.theoPowerSum) + ' kWh' : ''}`,
                irradiation: `Irradiation ${sum?.irrSum ? formatNumber(sum.irrSum) + ' kWh/m²' : ''}`,
                cosPhi: `CosPhi ${sum?.cosPhiSum ? formatNumber(sum.cosPhiSum) + ' kWh' : ''}`,
                p_set_gridop_rel: `PPC by Grid Operator `,
                p_set_rpc_rel: `PPC by RPC (Direktvermarkter)%`
            };

            const dynamicLines = Object.keys(data[0] || {})
                .filter(key => key !== 'date') // Exclude the 'date' key
                .map((key, index) => {
                    const color = keyColorMap[key] || `#${Math.floor(Math.random() * 16777215).toString(16)}`;

                    // Conditionally hide the line from the legend
                    const hideFromLegend = key === 'expexted_evu_good';

                    return (
                        <Line
                            key={key}
                            yAxisId={index % 2 === 0 ? "left" : "right"} // Alternate yAxis for diversity
                            type="monotone"
                            dataKey={key}
                            stroke={color}
                            strokeWidth={2}
                            dot={false}
                            name={hideFromLegend ? '' : customLegendNames[key] || key} // Hide name from legend if condition matches
                            legendType={hideFromLegend ? "none" : undefined} // Exclude line from the legend
                            hide={hiddenLines[key]} // Hide line if it's marked as hidden
                        />
                    );
                });
            setLines(dynamicLines);
        } catch (error) {
            console.error('Error fetching chart data', error);
        }
    };

    useEffect(() => {
        if (selectedRowData) {
            setForm(prevForm => ({
                ...prevForm,
                anlageId: selectedRowData.id
            }));
            fetchChartData();
        }
    }, [selectedRowData, startDate, endDate, form.selectedChart, form.toggleOption]);

    const handleFormChange = (e) => {
        setForm({
            ...form,
            [e.target.name]: e.target.value,
        });
    };

    const handleToggleChange = (checked) => {
        setForm({
            ...form,
            toggleOption: checked,
        });
    };

    const handleStartDateChange = (date) => {
        const selectedDate = date > maxDate ? maxDate : date;
        setStartDate(selectedDate);
        fetchChartData();
    };

    const handleEndDateChange = (date) => {
        const selectedDate = date > maxDate ? maxDate : date;
        setEndDate(selectedDate);
        fetchChartData();
    };

    const handleLegendClick = (e) => {
        const clickedKey = e.dataKey;

        setHiddenLines((prevState) => {
            if (clickedKey === 'expexted_evu') {
                return {
                    ...prevState,
                    expexted_evu: !prevState.expexted_evu,
                    expexted_evu_good: !prevState.expexted_evu_good,
                };
            } else {

                return {
                    ...prevState,
                    [clickedKey]: !prevState[clickedKey],
                };
            }
        });
    };


    useEffect(() => {
        fetchChartData();
    }, [form, startDate, endDate, hiddenLines]); // Fetch data whenever form data, dates, or hidden lines change



    const CustomTooltip = ({ active, payload, label }) => {
        if (!active || !payload || !payload.length) {
            return null;
        }

        // Mapping data keys to more human-readable names and units
        const dataKeyMapping = {
            expexted_evu: { name: 'Expected Grid', unit: 'kWh' },
            eZEvu: { name: 'Grid', unit: 'kWh' },
            expexted_evu_good: { name: 'Expected Grid Good', unit: 'kWh' },
            expected: { name: 'Expected', unit: 'kWh' },
            expgood: { name: 'Expected Good', unit: 'kWh' },
            InvOut: { name: 'Inverter Out', unit: 'kWh' },
            irradiation: { name: 'Irradiation', unit: 'W/m²' },
            default: { name: 'Unknown', unit: '' }
        };
        const formatNumber = (value) => {
            return value ? value.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
        };

        return (
            <div className="custom-tooltip" style={{ backgroundColor: '#fff', padding: '5px', border: '1px solid #ccc', fontSize: '0.8rem' }}>
                <p className="label">{`Date: ${label}`}</p>
                {payload.map((entry, index) => {
                    const { dataKey, value, stroke } = entry;
                    const { name, unit } = dataKeyMapping[dataKey] || dataKeyMapping.default;

                    const formatValue=formatNumber(value);
                    return (
                        <p key={`item-${index}`} style={{ color: stroke }}>
                            {`${name}: ${formatValue} ${unit}`}
                        </p>
                    );
                })}
            </div>
        );
    };




    return (
        <div style={{
            height: '100%',
            display: 'flex',
            padding: '0px 10px 0px 10px',
            backgroundColor: theme === 'light' ? '#ffffff' : '#343a40'
        }}>
            {selectedRowData ? (
                <div style={{ flex: 1, height: '100%' }}>
                    <div style={{ textAlign: 'center', fontSize: '0.8rem', marginBottom:'5px' }}>
                        <span className="fw-bolder">
                            {selectedRowData.name}_{type}
                        </span>
                    </div>
                    <div>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '5px', fontSize: '0.8rem', marginBottom:'5px'  }}>
                            <select
                                name="selectedChart"
                                value={form.selectedChart}
                                onChange={(e) => {
                                    handleFormChange(e);
                                    fetchChartData(); // Trigger data fetch on change
                                }}
                                style={{ fontSize: '0.8rem', padding: '2px 5px', height: '24px' }}>
                                {type === 'ac_single' && <option value="ac_single">AC</option>}
                                {type === 'dc_single' && <option value="dc_single">DC</option>}
                            </select>

                            <div>
                                <DatePicker
                                    selected={startDate}
                                    onChange={handleStartDateChange}
                                    selectsStart
                                    startDate={startDate}
                                    endDate={endDate}
                                    maxDate={maxDate}
                                    style={{ fontSize: '0.8rem', padding: '2px', height: '24px' }}
                                    calendarClassName="small-datepicker"
                                    dateFormat="yyyy-MM-dd"
                                />
                            </div>

                            <div>
                                <DatePicker
                                    selected={endDate}
                                    onChange={handleEndDateChange}
                                    selectsEnd
                                    startDate={startDate}
                                    endDate={endDate}
                                    minDate={startDate}
                                    maxDate={maxDate}
                                    style={{ fontSize: '0.8rem', padding: '2px', height: '24px' }}
                                    calendarClassName="small-datepicker"
                                    dateFormat="yyyy-MM-dd"
                                />
                            </div>

                            <div>
                                <CustomSwitch
                                    onChange={(checked) => {
                                        handleToggleChange(checked);
                                        fetchChartData(); // Trigger data fetch on change
                                    }}
                                    checked={form.toggleOption}
                                />
                            </div>
                        </div>

                        <ResponsiveContainer width="100%" height={400}>
                            <LineChart
                                data={chartData}
                                margin={{
                                    top: 5, right: 20, left: 0, bottom: 5,
                                }}
                            >
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis dataKey="date" tick={{ fontSize: 10 }} />
                                <YAxis yAxisId="left" tick={{ fontSize: 10 }} />
                                <YAxis yAxisId="right" orientation="right" tick={{ fontSize: 10 }} />
                                <Tooltip content={<CustomTooltip />} /> {/* Use custom tooltip */}
                                <Legend
                                    wrapperStyle={{ fontSize: '0.8rem' }}
                                    onClick={handleLegendClick} // Handle legend clicks to show/hide lines
                                />
                                {lines}
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                </div>
            ) : (
                <div className="panel-box" style={{ justifyContent: 'center', alignItems: 'center' }}>
                    <span>Loading... <i className="fas fa-cog fa-spin fa-3x"></i></span>
                </div>
            )}
        </div>
    );
};

export default Chart;






