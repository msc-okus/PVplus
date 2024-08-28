import React, { useState } from 'react';
import { Responsive, WidthProvider } from 'react-grid-layout';
import Overview from './Overview';
import Panel from './Panel';
import Chart from './Chart';
import NavBar from './NavBar';
import { Dropdown, DropdownButton } from 'react-bootstrap';
import '../styles/controllers_styles/dashboard.css';
import { useTheme } from "./ThemenContext";

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
    const [isFullScreen, setIsFullScreen] = useState(false);

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

    const toggleFullScreen = () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
            setIsFullScreen(true);
        } else if (document.exitFullscreen) {
            document.exitFullscreen();
            setIsFullScreen(false);
        }
    };

    return (
        <div style={{ height: '100%', display: "flex", flexDirection: "column" }}>
            <div className="d-flex justify-content-between my-2">
                <DropdownButton
                    id="dropdown-basic-button"
                    title={<i className="fas fa-plus" style={{
                        fontSize: '0.8rem',
                        borderRadius: '50%',
                        backgroundColor: '#ccc',
                        padding: '5px',
                        color: 'white'
                    }} title="Add a new window"></i>}
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
                <NavBar />
                <div className="d-flex justify-content-center align-items-center">
                    <i
                        className="fas fa-adjust"
                        onClick={toggleTheme}
                        title={theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'}
                        style={{ fontSize: '1.5rem', cursor: 'pointer' }}
                    ></i>
                    <i
                        className={isFullScreen ? "fas fa-compress" : "fas fa-expand"}
                        onClick={toggleFullScreen}
                        title= {isFullScreen ? "Exit fullscreen" : "View fullscreen"}
                        style={{ fontSize: '1.5rem', cursor: 'pointer', marginLeft: '10px' }}
                    ></i>
                </div>
            </div>
            <ResponsiveGridLayout
                style={{ flex: '1' }}
                layouts={layouts}
                onLayoutChange={(layout, layouts) => onLayoutChange(layout, layouts)}
            >
                {layouts.lg.map((item) => (
                    <div key={item.i} data-grid={item} style={{ position: "relative", background: "white" }}>
                        {item.i.startsWith('overview') ? (
                            <Overview itemId={item.i} setSelectedRowData={setSelectedRowData} />
                        ) : item.i.startsWith('panel') ? (
                            <Panel itemId={item.i} selectedRowData={selectedRowData} />
                        ) : item.i.startsWith('chart') ? (
                            <Chart itemId={item.i} selectedRowData={selectedRowData} />
                        ) : (
                            <span className="text">{item.i}</span>
                        )}
                        <button
                            onMouseDown={(e) => e.stopPropagation()}
                            onClick={(e) => {
                                e.stopPropagation();
                                removeItem(item.i);
                            }}
                            className="btn"
                            style={{position: "absolute", top: '0', right: '0', zIndex: '10'}}
                        >
                            <i className="fas fa-times" style={{fontSize: '1rem', color: '#000'}} title="close"></i>
                        </button>
                    </div>
                ))}
            </ResponsiveGridLayout>
        </div>
    );
};

const generateLayout = () => {
    return [
        { i: 'panel_0', x: 0, y: 0, w: 12, h: 2 },
        { i: 'overview_0', x: 0, y: 1, w: 12, h: 3 },
    ];
};

export default Dashboard;
