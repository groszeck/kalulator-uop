var chartInstance = null;
    var lastRequest = null;

    function initCalculator(){
        bindEvents();
        var initialInput = collectInput();
        fetchCalculation(initialInput);
    }

    function bindEvents(){
        var $form = $('#kpj-calc-form');
        $form.on('input change', 'input, select', debounce(onInputChange, 300));
        $(document).on('click', '.kpj-preset-btn', function(e){
            e.preventDefault();
            var amount = $(this).data('amount');
            applyPreset(amount);
        });
    }

    function onInputChange(){
        var input = collectInput();
        fetchCalculation(input);
    }

    function collectInput(){
        return {
            gross: parseFloat($('#kpj-salary-gross').val()) || 0,
            contract: $('#kpj-contract-type').val(),
            health: $('#kpj-health-insurance').is(':checked'),
            retirement: $('#kpj-retirement-insurance').is(':checked'),
            disability: $('#kpj-disability-insurance').is(':checked'),
            sickness: $('#kpj-sickness-insurance').is(':checked'),
            advance: $('#kpj-income-advance').is(':checked')
        };
    }

    function fetchCalculation(input){
        if(lastRequest){
            lastRequest.abort();
        }
        lastRequest = $.ajax({
            url: KpjCalc.ajax_url,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'kpj_calculate',
                nonce: KpjCalc.nonce,
                input: input
            },
            beforeSend: function(){
                showLoading();
                hideError();
            },
            success: function(response){
                if(response.success && response.data){
                    updateResults(response.data);
                } else {
                    var msg = (response.data && response.data.error) ? response.data.error : 'B??d oblicze?.';
                    handleError(msg);
                    clearResults();
                }
            },
            error: function(xhr, status){
                if(status !== 'abort'){
                    handleError('B??d po??czenia.');
                    clearResults();
                }
            },
            complete: function(){
                hideLoading();
            }
        });
    }

    function updateResults(data){
        hideError();
        $('#kpj-net-amount').text(data.net.toFixed(2));
        $('#kpj-total-tax').text(data.taxTotal.toFixed(2));
        $('#kpj-employer-cost').text(data.employerCost.toFixed(2));
        $.each(data.breakdown, function(key, val){
            var el = $('#kpj-' + key.replace('_','-'));
            if(el.length){
                el.text(val.toFixed(2));
            }
        });
        if(data.chart){
            renderChart(data.chart);
        }
    }

    function clearResults(){
        $('#kpj-net-amount, #kpj-total-tax, #kpj-employer-cost').text('0.00');
        $('[id^="kpj-"]').each(function(){
            var id = $(this).attr('id');
            if(id.match(/^kpj-[a-z]+(-[a-z]+)?$/)){
                $(this).text('0.00');
            }
        });
        if(chartInstance){
            chartInstance.data.labels = [];
            chartInstance.data.datasets[0].data = [];
            chartInstance.update();
        }
    }

    function renderChart(chartData){
        var ctxEl = document.getElementById('kpj-chart');
        if(!ctxEl) return;
        var ctx = ctxEl.getContext('2d');
        if(chartInstance){
            chartInstance.data.labels = chartData.labels;
            chartInstance.data.datasets[0].data = chartData.datasets[0].data;
            chartInstance.update();
        } else {
            chartInstance = new Chart(ctx, {
                type: 'pie',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    }

    function applyPreset(amount){
        $('#kpj-salary-gross').val(amount);
        onInputChange();
    }

    function debounce(fn, delay){
        var timer = null;
        return function(){
            var context = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function(){
                fn.apply(context, args);
            }, delay);
        };
    }

    function showLoading(){
        var $loader = $('#kpj-loading');
        if($loader.length){
            $loader.show();
        }
    }

    function hideLoading(){
        var $loader = $('#kpj-loading');
        if($loader.length){
            $loader.hide();
        }
    }

    function handleError(message){
        var $err = $('#kpj-error-message');
        if($err.length){
            $err.text(message).show();
        } else {
            console.warn(message);
        }
    }

    function hideError(){
        var $err = $('#kpj-error-message');
        if($err.length){
            $err.hide().text('');
        }
    }

    $(document).ready(initCalculator);
})(jQuery);