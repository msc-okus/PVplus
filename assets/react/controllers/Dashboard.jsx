import React, { useState, useEffect } from 'react';
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
    overview: { w: 12, h: 3, maxH: 5 ,minH: 3},
    panel: { w: 12, h: 2, maxH: 2, minH:2 },
    chart: { w: 4, h: 2, maxH: 5 ,minH: 2}
};

const Dashboard = ({ maxItems }) => {
    const [layouts, setLayouts] = useState({ lg: generateLayout() });
    const [counter, setCounter] = useState(4);
    const [selectedRowData, setSelectedRowData] = useState(null);
    const { theme, toggleTheme } = useTheme(); // Use the theme and toggleTheme from context
    const [isFullScreen, setIsFullScreen] = useState(false);
    const [rowHeight, setRowHeight] = useState(150); // Default value for rowHeight
    const [gridHeight, setGridHeight] = useState('auto');

    const onLayoutChange = (layout, layouts) => {
        setLayouts(layouts);
    };

    const addItem = (type) => {
        if (layouts.lg.length < maxItems) {
            const newLayouts = { ...layouts };
            const newItem = {
                i: `${type}_${counter}`,
                x: 0,
                y: 0,
                ...itemTypes[type],
                maxH: itemTypes[type].maxH,
                minH: itemTypes[type].minH,
            };
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



    useEffect(() => {
        const updateRowHeight = () => {
            const width = window.innerWidth;

            if (width >= 1400) { // xxl
                setRowHeight(150);
            } else if (width <= 1200) { // xl
                setRowHeight(120);
            }
        };

        window.addEventListener('resize', updateRowHeight);
        updateRowHeight(); // Set the initial value

        return () => window.removeEventListener('resize', updateRowHeight);
    }, []);

    useEffect(() => {
        const updateHeight = () => {
            const totalRows = 5; // Example, you might calculate this dynamically
            const margin = 15; // If you have a margin of [5, 5]
            const totalHeight = totalRows * (rowHeight + margin);
            setGridHeight(totalHeight);
        };

        updateHeight(); // Set the initial value
    }, [rowHeight]);
    return (
        <div style={{height: '100%' ,border:'1px solid transparent'}}>
            <div className="d-flex justify-content-between " style={{ marginBottom:'5px'}}>
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
                <NavBar/>
                <div className="d-flex justify-content-center align-items-center">
                    <i
                        className="fas fa-adjust"
                        onClick={toggleTheme}
                        title={theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'}
                        style={{fontSize: '1.5rem', cursor: 'pointer'}}
                    ></i>
                    <i
                        className={isFullScreen ? "fas fa-compress" : "fas fa-expand"}
                        onClick={toggleFullScreen}
                        title={isFullScreen ? "Exit fullscreen" : "View fullscreen"}
                        style={{fontSize: '1.5rem', cursor: 'pointer', marginLeft: '10px'}}
                    ></i>
                </div>
            </div>
            <div style={{ height: `${gridHeight}px` , border:`1px solid ${theme === 'dark'?'#5e5d5d':'#b9b7b7'}`}}>
            <ResponsiveGridLayout
                rowHeight={rowHeight}
                margin={[5,5]}
                layouts={layouts}
                onLayoutChange={(layout, layouts) => onLayoutChange(layout, layouts)}
                isDraggable={true}
                isResizable={true}
                allowOverlap={true}
                useCSSTransforms={true} // Smoother dragging and resizing

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
                            className="btn"
                            style={{position: "absolute", top: '0', right: '0', zIndex: '10'}}
                        >
                            <i className="fas fa-times" style={{fontSize: '1rem', color: '#000'}} title="close"></i>
                        </button>
                    </div>
                ))}
            </ResponsiveGridLayout>
            </div>
            <div className="d-flex justify-content-center align-items-center" style={{  marginTop:'5px'}}>

                <nav
                    className={`navbar navbar-expand-lg ${theme === 'light' ? 'navbar-light bg-light' : 'navbar-dark bg-dark'}`}>
                    <div className="container-fluid">
                        <div className="collapse navbar-collapse ">
                            <p className="text-center m-0">&copy; 2018 - 2024 Green4Net - All Rights Reserved - PV+ 4.0
                                v2.5.1 beta</p>
                        </div>
                    </div>
                </nav>

            </div>
        </div>
    );
};

const generateLayout = () => {
    return [
        {i: 'panel_0', x: 0, y: 0, w: 12, h: 2,maxH: 2 , minH:2},
        {i: 'overview_0', x: 0, y: 2, w: 12, h: 3,maxH: 5 , minH:3},
    ];
};

export default Dashboard;
