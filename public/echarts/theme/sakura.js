/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

(function(root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['exports', 'echarts'], factory);
    } else if (
        typeof exports === 'object' &&
        typeof exports.nodeName !== 'string'
    ) {
        // CommonJS
        factory(exports, require('echarts'));
    } else {
        // Browser globals
        factory({}, root.echarts);
    }
})(this, function(exports, echarts) {
    var log = function(msg) {
        if (typeof console !== 'undefined') {
            console && console.error && console.error(msg);
        }
    };
    if (!echarts) {
        log('ECharts is not Loaded');
        return;
    }

    var colorPalette = [
        '#098f09',
        '#faee08',
        '#fa506c',
        '#999999',
        '#f8c4d8',
        '#e54f5c',
        '#0000ff',
        '#e54f80',
        '#f29c9f',
        '#00ff00'
    ];

    var theme = {
        color: colorPalette,

        title: {
            textStyle: {
                fontWeight: 'normal',
                color: '#0022ff'
            }
        },

        visualMap: {
            color: ['#e52c3c', '#00ff00']
        },

        dataRange: {
            color: ['#e52c3c', '#00ff00']
        },

        candlestick: {
            itemStyle: {
                color: '#00ff00',
                color0: '#f59288'
            },
            lineStyle: {
                width: 1,
                color: '#e52c3c',
                color0: '#f59288'
            },
            areaStyle: {
                color: '#fa506c',
                color0: '#00ff00'
            }
        },

        map: {
            itemStyle: {
                color: '#e52c3c',
                borderColor: '#fff',
                borderWidth: 1
            },
            areaStyle: {
                color: '#ccc'
            },
            label: {
                color: 'rgba(139,69,19,1)',
                show: false
            }
        },

        graph: {
            itemStyle: {
                color: '#00ff00'
            },
            nodeStyle: {
                brushType: 'both',
                strokeColor: '#e54f5c'
            },
            linkStyle: {
                color: '#f2385a',
                strokeColor: '#e54f5c'
            },
            label: {
                color: '#f2385a',
                show: false
            }
        },

        gauge: {
            axisLine: {
                lineStyle: {
                    color: [
                        [0.2, '#e52c3c'],
                        [0.8, '#f7b1ab'],
                        [1, '#00ff00']
                    ],
                    width: 8
                }
            }
        }
    };

    echarts.registerTheme('sakura', theme);
});
