import React, { useState } from 'react';
import { Responsive, WidthProvider } from 'react-grid-layout';
import Overview from './Overview';
import Panel from './Panel';
import Chart from './Chart';
import NavBar from './NavBar';
import { Dropdown, DropdownButton, Button } from 'react-bootstrap';
import '../styles/controllers_styles/dashboard.css';
import {useTheme} from "./ThemenContext";

const ResponsiveGridLayout = WidthProvider(Responsive);

const itemTypes = {
    overview: { w: 12, h: 3 },
    panel: { w: 12, h: 2 },
    chart: { w: 4, h: 2 }
};

const Dashboard = ({ maxItems }) => {
    const [layouts, setLayouts] = useState({ lg: generateLayout() });
    const [counter, setCounter] = useState(4);
    const [selectedRowData, setSelectedRowData] = useState(null);
    const { theme, toggleTheme } = useTheme(); // Use the theme and toggleTheme from context

    const onLayoutChange = (layout, layouts) => {
        setLayouts(layouts);
    };

    const addItem = (type) => {
        if (layouts.lg.length < maxItems) {
            const newLayouts = { ...layouts };
            const newItem = { i: `${type}_${counter}`, x: 0, y: 0, ...itemTypes[type] };
            newLayouts.lg.push(newItem);
            setLayouts(newLayouts);
            setCounter(counter + 1);
        }
    };

    const removeItem = (itemId) => {
        const newLayouts = {
            lg: layouts.lg.filter(item => item.i !== itemId)
        };
        setLayouts(newLayouts);
    };

    const availableItemTypes = Object.keys(itemTypes);

    return (
        <div style={{height: '100%', display: "flex", flexDirection: "column"}}>
            <div className="d-flex justify-content-between my-2">
                <DropdownButton
                    id="dropdown-basic-button"
                    title={<i className="fas fa-plus" style={{
                        fontSize: '0.8rem',
                        borderRadius: '50%',
                        backgroundColor: '#ccc',
                        padding: '5px',
                        color: 'white'
                    }}></i>}
                    variant="white"
                    drop="end"
                    className=" custom-dropdown d-flex justify-content-center align-items-center"
                >
                    <Dropdown.Header className="text-center">Manage Your Panel</Dropdown.Header>
                    {availableItemTypes.map(type => (
                        <Dropdown.Item
                            key={type}
                            onClick={() => addItem(type)}
                        >
                            {type.charAt(0).toUpperCase() + type.slice(1)}
                        </Dropdown.Item>
                    ))}
                </DropdownButton>
                <NavBar/>
                <div className="d-flex justify-content-center align-items-center">
                    <i
                        className="fas fa-adjust"
                        onClick={toggleTheme}
                        style={{fontSize: '1.5rem', cursor: 'pointer'}}
                    ></i>
                </div>
            </div>
            <ResponsiveGridLayout
                style={{flex: '1'}}
                layouts={layouts}
                onLayoutChange={(layout, layouts) => onLayoutChange(layout, layouts)}
            >
            {layouts.lg.map((item) => (
                    <div key={item.i} data-grid={item} style={{position: "relative", background: "white"}}>
                        {item.i.startsWith('overview') ? (
                            <Overview itemId={item.i} setSelectedRowData={setSelectedRowData}/>
                        ) : item.i.startsWith('panel') ? (
                            <Panel itemId={item.i} selectedRowData={selectedRowData}/>
                        ) : item.i.startsWith('chart') ? (
                            <Chart itemId={item.i} selectedRowData={selectedRowData}/>
                        ) : (
                            <span className="text">{item.i}</span>
                        )}
                        <button
                            onMouseDown={(e) => e.stopPropagation()}
                            onClick={(e) => {
                                e.stopPropagation();
                                removeItem(item.i);
                            }}
                            className="btn-close"
                            style={{position: "absolute", top: '0', right: '0', zIndex: '10'}}
                        >
                        </button>
                    </div>
                ))}
            </ResponsiveGridLayout>
        </div>
    );
};

const generateLayout = () => {
    return [
        {i: 'panel_0', x: 0, y: 0, w: 12, h: 2},
        {i: 'overview_0', x: 0, y: 1, w: 12, h: 3},
    ];
};

export default Dashboard;
