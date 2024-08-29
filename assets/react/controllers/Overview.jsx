import React, { useEffect, useState } from 'react';
import { useReactTable, getCoreRowModel, getPaginationRowModel, getSortedRowModel, getFilteredRowModel, flexRender } from '@tanstack/react-table';
import axios from 'axios';
import ManageColumnsVisibility from "./ ManageColumnsVisibility";
import {useTheme} from "./ThemenContext";



const Overview = ({ itemId, setSelectedRowData}) => {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [columnVisibility, setColumnVisibility] = useState({ id: false });
    const [dropdownVisible, setDropdownVisible] = useState(false);
    const [sorting, setSorting] = useState([]);
    const [globalFilter, setGlobalFilter] = useState('');
    const [columnFilters, setColumnFilters] = useState([]);
    const [selectedRowData, setSelectedRowDataLocal] = useState(null);
    const [isG4N, setIsG4N] = useState(false);
    const { theme } = useTheme();


    const fetchData = (showLoading = true) => {
        if (showLoading) {
            setLoading(true);
        }
        axios.get('/new/retrieve_plants')
            .then(response => {
                setIsG4N(response.data.isG4n);
                const sortedData = sortData(response.data.plants);
                setData(sortedData);
                if (showLoading) {
                    setLoading(false);
                }

            })
            .catch(error => {
                console.error("There was an error fetching the plants data!", error);
                if (showLoading) {
                    setLoading(false);
                }
            });
    };

    const sortData = (plants) => {
        return plants.sort((a, b) => {
            const colorA = JSON.parse(a.status).color.toLowerCase();
            const colorB = JSON.parse(b.status).color.toLowerCase();
            const order = { 'red': 1, 'orange': 2, 'blue': 3, 'green': 4, 'black': 5 };
            return order[colorA] - order[colorB];
        });
    };

    // Chargement initial des données
    useEffect(() => {
        fetchData();
    }, []);

    useEffect(() => {
        const intervalId = setInterval(()=>fetchData(false), 30000);
        return () => clearInterval(intervalId);
    }, []);


    useEffect(() => {
        if (!loading && data.length > 0) {
            if(selectedRowData){
                handleRowClick(selectedRowData);
            }else{
                handleRowClick(data[0]);
            }

        }
    }, [loading, data]);



    const columns = [
        {
            accessorKey: 'id',
            header: 'ID'
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: info => {
                const status = JSON.parse(info.getValue());
                const color= status.color
                return (
                    <span
                        style={{
                            display: 'inline-block',
                            width: '15px',
                            height: '15px',
                            borderRadius: '50%',
                            backgroundColor: color,
                            marginRight: '5px'
                        }}
                    >
                    </span>
                );
            }
        },
        {
            accessorKey: 'name',
            header: 'Name',
            filterFn: 'includesString'
        },
        isG4N && {
            accessorKey: 'firma',
            header: 'Company'
        },
        {
            accessorKey: 'country',
            header: 'Country',
            cell: info => {
                const country = info.getValue();
                const imageUrl = `/images/flag/flag-${ country?country.toLowerCase():'de'}.png`;
                return (
                    <img
                        src={imageUrl}
                        alt={country}
                        style={{
                            width: '20px',
                            height: '20px',
                        }}
                    />
                );
            }
        },

        {
            accessorKey: 'anlBetrieb',
            header: 'Installation date'
        },
        {
            accessorKey: 'pnom',
            header: 'Total string capacity (kWp)'
        },
        {
            accessorKey: 'pr_act',
            header: 'Performance (kWh)',
           /* header: info => {
                const firstRowPerformance = info.table.getRowModel().rows[0]?.getValue('pr_act');
                const performance = firstRowPerformance ? JSON.parse(firstRowPerformance) : [];
                return performance['lastDataIo'] ? `Performance at  ${performance['lastDataIo']} (kWh)` : 'Performance (kWh)';
            },*/
            cell: info => {
                const performance = JSON.parse(info.getValue());
                return (
                    <div>
                        <span>{performance['acActAll']}</span>
                    </div>
                );
            }
        },
        {
            accessorKey: 'pr_exp',
            header: 'Expected performance (kWh)',
           /* header: info => {
                const firstRowPerformance = info.table.getRowModel().rows[0]?.getValue('pr_exp');
                const performance = firstRowPerformance ? JSON.parse(firstRowPerformance) : [];
                return performance['lastDataIo'] ? `Expected performance at  ${performance['lastDataIo']} (kWh)` : 'Expected performance (kWh)';
            },*/
            cell: info => {
                const performance = JSON.parse(info.getValue());
                return (
                    <div>
                        <span>{performance['acExpAll']}</span>
                    </div>
                );
            }
        },
        {
            accessorKey: 'pr_year',
            header: ' Annual total energy yield(kWh)',
            cell: info => {
                const pr = JSON.parse(info.getValue());
                return (
                    <div>
                        <span>{pr['power']}</span>
                    </div>
                );
            }
        },
    ].filter(Boolean); // Filter out any false values from the array

    const table = useReactTable({
        data,
        columns,
        state: { columnVisibility, sorting, globalFilter, columnFilters },
        onColumnVisibilityChange: setColumnVisibility,
        onSortingChange: setSorting,
        onColumnFiltersChange: setColumnFilters,
        getCoreRowModel: getCoreRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        initialState: {
            pagination: { pageSize: 10 }

        },
    });

    const handleRowClick = (rowData) => {
        setSelectedRowData(rowData);
        setSelectedRowDataLocal(rowData);

    };

    return (
        <div className="overview" style={{ height: '100%', width: '100%', display: 'flex', flexDirection: 'column' ,backgroundColor:theme === 'light' ? '#ffffff' : '#343a40' }}>
            {loading ? (
                <div className="panel-box" style={{justifyContent: 'center', alignItems: 'center'}}>
                    <span>Loading... <i className="fas fa-cog fa-spin fa-3x"></i></span>
                </div>
            ) : (
                <div style={{
                    height: '100%',
                    width: '100%',
                    display: 'flex',
                    flexDirection: 'column',
                    overflow: 'hidden'
                }}>
                    <div>
                        <label>
                            Plant name
                            <input
                                value={table.getColumn('name')?.getFilterValue() || ''}
                                onMouseDown={(e) => e.stopPropagation()}
                                onClick={(e) => e.stopPropagation()}
                                onChange={e => table.getColumn('name')?.setFilterValue(e.target.value)}
                                placeholder={`Plant Name`}
                                style={{
                                    border: '1px solid #ccc',
                                    borderRadius: '4px',
                                    padding: '10px',
                                    margin: '10px'
                                }}
                            />
                        </label>
                    </div>
                    <ManageColumnsVisibility table={table}/>
                    <div style={{flex: '1 1 auto', overflowY: 'auto'}}>
                        <table className={`table  ${theme === 'light' ? '':'table-dark'} table-striped table-hover`} style={{width: '100%', fontSize: "0.9rem"}}>
                            <thead style={{position: 'sticky', top: 0, background: 'white'}}>
                            {table.getHeaderGroups().map(headerGroup => (
                                <tr key={headerGroup.id}>
                                    {headerGroup.headers.map(header => (
                                        <th key={header.id}
                                            style={{border: '1px solid #ddd', padding: '8px', textAlign: 'left'}}>
                                            <div style={{display: 'flex', alignItems: 'center'}}>
                                                {flexRender(header.column.columnDef.header, header.getContext())}
                                                <button
                                                    onMouseDown={(e) => e.stopPropagation()}
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        setSorting([{id: header.column.id, desc: false}]);
                                                    }}
                                                    style={{
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent: 'center',
                                                        width: '24px',
                                                        height: '24px',
                                                        marginLeft: '5px',
                                                        background: 'none',
                                                        border: 'none',
                                                        cursor: 'pointer',
                                                        color: theme === 'light' ?'black':'white'
                                                    }}
                                                >
                                                    <i className="fas fa-sort-up"></i>
                                                </button>
                                                <button
                                                    onMouseDown={(e) => e.stopPropagation()}
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        setSorting([{id: header.column.id, desc: true}]);
                                                    }}
                                                    style={{
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent: 'center',
                                                        width: '24px',
                                                        height: '24px',
                                                        marginLeft: '5px',
                                                        background: 'none',
                                                        border: 'none',
                                                        cursor: 'pointer',
                                                        color: theme === 'light' ?'black':'white'
                                                    }}
                                                >
                                                    <i className="fas fa-sort-down"></i>
                                                </button>
                                            </div>
                                        </th>
                                    ))}
                                </tr>
                            ))}
                            </thead>
                            <tbody>
                            {table.getRowModel().rows.map((row,index) => (
                                <tr
                                    key={row.id}
                                    onMouseDown={(e) => e.stopPropagation()}
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        handleRowClick(row.original);
                                    }}
                                    className={row.original.id === selectedRowData?.id ? 'table-primary' : ''}
                                >
                                    {row.getVisibleCells().map(cell => (
                                        <td key={cell.id} style={{border: '1px solid #ddd', padding: '8px'}}>
                                            {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                            </tbody>
                        </table>
                    </div>
                    <div className="pagination-overview"
                         style={{display: 'flex', justifyContent: 'space-between', padding: '8px'}}>
                        <div>
                            <select
                                value={table.getState().pagination.pageSize}
                                onChange={e => {
                                    table.setPageSize(Number(e.target.value));
                                }}

                            >
                                {[10, 20, 30, 40, 50].map(pageSize => (
                                    <option key={pageSize} value={pageSize}>
                                        {pageSize}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <button
                                onMouseDown={(e) => e.stopPropagation()}
                                onClick={(e) => {
                                    e.stopPropagation();
                                    table.setPageIndex(0);
                                }}
                                disabled={!table.getCanPreviousPage()}
                            >
                                {'<<'}
                            </button>
                            <button
                                onMouseDown={(e) => e.stopPropagation()}
                                onClick={(e) => {
                                    e.stopPropagation();
                                    table.previousPage();
                                }}
                                disabled={!table.getCanPreviousPage()}
                            >
                                {'<'}
                            </button>
                            <span >
                                {table.getState().pagination.pageIndex + 1} / {table.getPageCount()}
                            </span>
                            <button
                                onMouseDown={(e) => e.stopPropagation()}
                                onClick={(e) => {
                                    e.stopPropagation();
                                    table.nextPage();
                                }}
                                disabled={!table.getCanNextPage()}
                            >
                                {'>'}
                            </button>
                            <button
                                onMouseDown={(e) => e.stopPropagation()}
                                onClick={(e) => {
                                    e.stopPropagation();
                                    table.setPageIndex(table.getPageCount() - 1);
                                }}
                                disabled={!table.getCanNextPage()}
                            >
                                {'>>'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default Overview;
