import React, { useRef, useEffect, useState } from 'react';
import { Dropdown, Button, Form } from 'react-bootstrap';



const ManageColumnsVisibility = ({ table }) => {
    const [dropdownVisible, setDropdownVisible] = useState(false);
    const dropdownRef = useRef(null);

    const handleClickOutside = (event) => {
        if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
            setDropdownVisible(false);
        }
    };

    useEffect(() => {
        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    return (
        <div style={{ width: '100%', margin: '0', display: "flex" }}>
            <div style={{ position: "relative", display: 'flex', flex: 1, justifyContent: 'end' }} ref={dropdownRef}>
                <Dropdown show={dropdownVisible} drop="start" className="custom-dropdown">
                    <Dropdown.Toggle
                        as={Button}
                        variant="white"
                        style={{ margin: '2px', padding: '2px' }}
                        onMouseDown={(e) => e.stopPropagation()}
                        onClick={() => setDropdownVisible(!dropdownVisible)}
                    >
                        <i className="fa fa-wrench" title="Manage columns visibility" aria-hidden="true" style={{ fontSize: '0.8rem', borderRadius: '50%', backgroundColor: '#ccc', padding: '5px',color:'white' }}></i>
                    </Dropdown.Toggle>
                    <Dropdown.Menu style={{ position: 'absolute', top: 'auto', left: 'auto', transform: 'translate(-100%, -100%)' }}>
                        <Dropdown.Header className="text-center">Manage columns visibility</Dropdown.Header>
                        <div className="d-flex flex-row px-3">
                            {table.getAllColumns().filter(column => column.id !== 'id').map(column => (
                                <div className="mx-2" key={column.id}>
                                    <Form.Check
                                        type="checkbox"
                                        id={column.id}
                                        checked={column.getIsVisible()}
                                        onChange={(e) => {
                                            column.toggleVisibility();
                                        }}
                                        onMouseDown={(e) => e.stopPropagation()}
                                    />
                                    <label className="nowrap-label" htmlFor={column.id}>
                                        {column.columnDef.header}
                                    </label>
                                </div>
                            ))}
                        </div>
                    </Dropdown.Menu>
                </Dropdown>
            </div>
        </div>
    );
};

export default ManageColumnsVisibility;
