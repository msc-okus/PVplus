//PageNotFound.jsx
import React from 'react';
import { useParams } from 'react-router-dom';
import NavBar from "./NavBar";

const PageNotFound = () => {
    const { id } = useParams();
    return (
        <div>
            <NavBar/> {/* Include NavBar component */}
            <div className="container mt-4">
                <h3>Page Not Found</h3>
                {id && <p>Selected Row ID: {id}</p>}
            </div>
        </div>
    );
};

export default PageNotFound;
