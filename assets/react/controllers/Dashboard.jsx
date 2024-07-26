import React, { useState } from 'react';
import { Responsive, WidthProvider } from 'react-grid-layout';
import Overview from './Overview';
import Panel from './Panel';
import Chart from './Chart';
import '../styles/dashboard.css';

const ResponsiveGridLayout = WidthProvider(Responsive);

const itemTypes = {
    overview: { w: 12, h: 2 },
    panel: { w: 12, h: 2 },
    chart: { w: 4, h: 2 }
};

const Dashboard = ({ maxItems }) => {
    const [layouts, setLayouts] = useState({ lg: generateLayout() });
    const [counter, setCounter] = useState(4);
    const [dropdownVisible, setDropdownVisible] = useState(false);
    const [selectedRowData, setSelectedRowData] = useState(null);

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
        setDropdownVisible(false);
    };

    const removeItem = (itemId) => {
        const newLayouts = {
            lg: layouts.lg.filter(item => item.i !== itemId)
        };
        setLayouts(newLayouts);
    };

    const availableItemTypes = Object.keys(itemTypes);

    return (
        <div>
            <div style={{ position: "relative" }}>
                <button
                    onClick={() => setDropdownVisible(!dropdownVisible)}
                    className={layouts.lg.length >= maxItems ? 'btn btn-primary disabled' : 'btn btn-primary'}
                    style={{ margin: 0 }}
                >
                    <i className="fa fa-caret-down" aria-hidden="true"></i>
                </button>
                {dropdownVisible && (
                    <div className="dropdown-type">
                        <div style={{ display: "flex", justifyContent: 'center' }}><span>Manage Your Panel</span>
                        </div>
                        {availableItemTypes.map(type => (
                            <div
                                key={type}
                                onClick={() => addItem(type)}
                                className="dropdown-type-item"
                            >
                                {type.charAt(0).toUpperCase() + type.slice(1)}
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <ResponsiveGridLayout
                style={{ background: "lightgray" }}
                layouts={layouts}
                onLayoutChange={(layout, layouts) => onLayoutChange(layout, layouts)}
            >
                {layouts.lg.map((item) => (
                    <div key={item.i} data-grid={item} style={{ position: "relative", background: "white" }}>
                        {item.i.startsWith('overview') ? (
                            <Overview itemId={item.i} setSelectedRowData={setSelectedRowData} />
                        ) :  item.i.startsWith('panel') ? (
                            <Panel itemId={item.i} selectedRowData={selectedRowData} />
                        ) : item.i.startsWith('chart') ? (
                            <Chart itemId={item.i} selectedRowData={selectedRowData} />
                        ) :  (
                            <span className="text">{item.i}</span>
                        )}
                        <button
                            onMouseDown={(e) => e.stopPropagation()}
                            onClick={(e) => {
                                e.stopPropagation();
                                removeItem(item.i);
                            }}
                            className="btn-close"
                            style={{ position: "absolute", top: '0', right: '0', zIndex: '10' }}
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
        { i: 'panel_0', x: 0, y: 0, w: 12, h: 2 },
        { i: 'overview_0', x: 0, y: 1, w: 12, h: 2 },
    ];
};

export default Dashboard;
