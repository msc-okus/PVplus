import React from 'react';
import { useParams } from 'react-router-dom';

const PageNotFound = () => {
    const { id } = useParams();
    return (
        <div>
            <h3>Page Not Found</h3>
            {id && <p>Selected Row ID: {id}</p>}

        </div>
    );
};

export default PageNotFound;
