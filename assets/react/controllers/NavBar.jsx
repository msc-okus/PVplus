import React from 'react';
import { NavLink } from 'react-router-dom';
import {useTheme} from "./ThemenContext";

const NavBar = () => {
    const { theme } = useTheme(); // Use the theme from context

    return (
        <nav className={`navbar navbar-expand-lg ${theme === 'light' ? 'navbar-light bg-light' : 'navbar-dark bg-dark'}`}>
            <div className="container-fluid">
                <button className="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span className="navbar-toggler-icon"></span>
                </button>
                <div className="collapse navbar-collapse" id="navbarNav">
                    <ul className="navbar-nav">
                        <li className="nav-item">
                            <NavLink className="nav-link" to="/new" end>Dashboard</NavLink>
                        </li>
                        <li className="nav-item">
                            <NavLink className="nav-link" to="/new/alerts">Alerts</NavLink>
                        </li>
                        <li className="nav-item">
                            <NavLink className="nav-link" to="/new/status">Status</NavLink>
                        </li>
                        <li className="nav-item">
                            <NavLink className="nav-link" to="/new/performance">Performance</NavLink>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    );
};

export default NavBar;
