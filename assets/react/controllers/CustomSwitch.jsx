import React from 'react';
import Switch from 'react-switch';

const CustomSwitch = ({ checked, onChange }) => {
    return (
        <Switch
            onChange={onChange}
            checked={checked}
            height={16} // Reduced height
            width={36} // Reduced width
            handleDiameter={14} // Reduced handle diameter
            offColor="#ccc"
            onColor="#4caf50"
            uncheckedIcon={
                <div
                    style={{
                        display: 'flex',
                        justifyContent: 'center',
                        alignItems: 'center',
                        height: '100%',
                        fontSize: 10,
                        color: 'black',
                        paddingRight: 2, // Adjust padding for better centering
                    }}
                >
                    15
                </div>
            }
            checkedIcon={
                <div
                    style={{
                        display: 'flex',
                        justifyContent: 'center',
                        alignItems: 'center',
                        height: '100%',
                        fontSize: 10,
                        color: 'white',
                        paddingRight: 2, // Adjust padding for better centering
                    }}
                >
                    60
                </div>
            }
            boxShadow="0px 1px 5px rgba(0, 0, 0, 0.6)"
            activeBoxShadow="0px 0px 1px 10px rgba(0, 0, 0, 0.2)"
            className="react-switch"
        />
    );
};

export default CustomSwitch;
