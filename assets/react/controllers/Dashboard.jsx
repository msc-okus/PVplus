import '../styles/dashboard.css';
import React, { useState } from 'react';
import { Responsive, WidthProvider } from 'react-grid-layout';

const ResponsiveGridLayout = WidthProvider(Responsive);

const itemTypes = {
    overview: { w: 12, h: 3 },
    chart: { w: 4, h: 2 },
    report: { w: 4, h: 2 },
    mini_overview: { w: 4, h: 2 },
    alert: { w: 4, h: 2 }
};

const Dashboard = ({ maxItems }) => {
    const [layouts, setLayouts] = useState({ lg: generateLayout() });
    const [counter, setCounter] = useState(4);
    const [dropdownVisible, setDropdownVisible] = useState(false);

    const onLayoutChange = (layout, layouts) => {
        setLayouts(layouts);
    };

    const addItem = (type) => {
        if (layouts.lg.length < maxItems /*&& !layouts.lg.some(item => item.i.startsWith(type))*/) {
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
            <div style={{position:"relative"}}>
                <button
                    onClick={() => setDropdownVisible(!dropdownVisible)}
                    className="btn btn-primary"
                    disabled={layouts.lg.length >= maxItems}
                    style={{margin:'0'}}
                >
                    add
                    <i className="fa fa-caret-down" aria-hidden="true"></i>
                </button>
                {dropdownVisible && (
                    <div className="dropdown-type">
                        {availableItemTypes.map(type => (
                            <div
                                key={type}
                                onClick={() => /*!layouts.lg.some(item => item.i.startsWith(type)) &&*/ addItem(type)}
                                // className={`dropdown-type-item ${layouts.lg.some(item => item.i.startsWith(type)) ? 'disabled' : ''}`}
                                 className="dropdown-type-item"
                            >
                                 {type.charAt(0).toUpperCase() + type.slice(1)}
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <ResponsiveGridLayout
                className="layout"
                layouts={layouts}
                onLayoutChange={(layout, layouts) => onLayoutChange(layout, layouts)}
            >
                {layouts.lg.map((item) => (
                    <div key={item.i} data-grid={item} className="react-grid-item-my">
                        <span className="text">{item.i}</span>
                        <button
                            onMouseDown={(e) => e.stopPropagation()}
                            onClick={(e) => {
                                e.stopPropagation();
                                removeItem(item.i);
                            }}
                            className="btn-close"
                            style={{position:"absolute" ,top:'0',right:'0',zIndex:'10'}}
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
        { i: 'mini_overview_2', x: 0, y: 0, w: 4, h: 2 },
        { i: 'report_1', x: 4, y: 0, w: 4, h: 2 },
        { i: 'alert_3', x: 8, y: 0, w: 4, h: 2 },
        { i: 'overview_0', x: 0, y: 1, w: 12, h: 3 },
    ];
};

export default Dashboard;
