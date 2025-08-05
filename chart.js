var ChartComponent = {
        initChart: function(canvasId, data, options) {
            if (typeof Chart !== 'function') {
                throw new Error('Chart.js not loaded');
            }
            var canvas = document.getElementById(canvasId);
            if (!canvas) {
                throw new Error('Canvas element not found: ' + canvasId);
            }
            var ctx = canvas.getContext('2d');
            var defaultOptions = {
                responsive: true,
                maintainAspectRatio: false
            };
            options = Object.assign({}, defaultOptions, options || {});
            var type = options.type || 'bar';
            delete options.type;
            var config = {
                type: type,
                data: data || {},
                options: options
            };
            try {
                return new Chart(ctx, config);
            } catch (e) {
                throw new Error('Failed to initialize chart: ' + e.message);
            }
        },

        updateChartData: function(chartInstance, newData) {
            if (!chartInstance || typeof chartInstance.update !== 'function') {
                throw new Error('Invalid chart instance');
            }
            if (newData.labels) {
                chartInstance.data.labels = newData.labels;
            }
            if (Array.isArray(newData.datasets)) {
                newData.datasets.forEach(function(dataset, index) {
                    if (chartInstance.data.datasets[index]) {
                        chartInstance.data.datasets[index] = Object.assign(
                            {},
                            chartInstance.data.datasets[index],
                            dataset
                        );
                    } else {
                        chartInstance.data.datasets[index] = dataset;
                    }
                });
            }
            try {
                chartInstance.update();
            } catch (e) {
                throw new Error('Failed to update chart data: ' + e.message);
            }
            return chartInstance;
        },

        destroyChart: function(chartInstance) {
            if (!chartInstance || typeof chartInstance.destroy !== 'function') {
                throw new Error('Invalid chart instance');
            }
            try {
                chartInstance.destroy();
            } catch (e) {
                throw new Error('Failed to destroy chart instance: ' + e.message);
            }
            return chartInstance;
        }
    };

    window.KPJ.ChartComponent = ChartComponent;
})(window, document, window.Chart);