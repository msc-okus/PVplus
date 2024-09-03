import React, { useState, useEffect } from 'react';
import DatePicker from 'react-datepicker';
import 'react-datepicker/dist/react-datepicker.css';
import axios from 'axios';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import { useTheme } from './ThemenContext';
import CustomSwitch from './CustomSwitch';

const Chart = ({ itemId, selectedRowData, type }) => {
    const { theme } = useTheme();
    const [startDate, setStartDate] = useState(new Date());
    const [endDate, setEndDate] = useState(new Date());
    const [chartData, setChartData] = useState([]);
    const [lines, setLines] = useState([]);
    const [form, setForm] = useState({
        anlageId: selectedRowData?.id || '',
        selectedChart: type,
        toggleOption: false,
    });

    const maxDate = new Date(); // Current date as the maximum selectable date

    // Key-color mapping
    const keyColorMap = {
        expected_evu: '#ffc658',
        expected_evu_good: '#ffc658',
        InvOut: '#8884d8',
        eZEvu: '#82ca9d',
        theoPower: '#525252',
        irradiation: '#773B4D',
        // Add more keys and colors as needed
    };

    // Function to generate a unique color
    const generateUniqueColor = (usedColors) => {
        let color;
        do {
            color = `#${Math.floor(Math.random() * 16777215).toString(16)}`;
        } while (usedColors.includes(color));
        return color;
    };

    const fetchChartData = async () => {
        try {
            const response = await axios.post('/new/chart', {
                ...form,
                startDate: startDate.toISOString(),
                endDate: endDate.toISOString(),
            });

            const data = JSON.parse(response.data[0].data);
            setChartData(data);

            // Collect all used colors in the keyColorMap
            const usedColors = Object.values(keyColorMap);

            // Map of key to custom legend names
            const customLegendNames = {
                eZEvu: 'Grid',
                // Add more custom names if needed
            };

            // Generate lines based on keys in the data, excluding the 'date' key
            const dynamicLines = Object.keys(data[0] || {})
                .filter(key => key !== 'date') // Exclude the 'date' key
                .map((key, index) => {
                    const color = keyColorMap[key] || generateUniqueColor(usedColors);
                    usedColors.push(color); // Add the new color to the list of used colors

                    return (
                        <Line
                            key={key}
                            yAxisId={index % 2 === 0 ? "left" : "right"} // Alternate yAxis for diversity
                            type="monotone"
                            dataKey={key}
                            stroke={color}
                            strokeWidth={2}
                            name={customLegendNames[key] || key} // Use custom legend name if available
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

    useEffect(() => {
        fetchChartData();
    }, [form, startDate, endDate]); // Fetch data whenever form data or dates change

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
                                <Tooltip contentStyle={{ fontSize: '0.8rem' }} />
                                <Legend wrapperStyle={{ fontSize: '0.8rem' }} />
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
