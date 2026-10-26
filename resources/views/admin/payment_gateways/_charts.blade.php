{{-- رسوم لوحات المدفوعات (ApexCharts المحلية). المتغيرات: $palette (ألوان السلاسل) --}}
<script src="{{ asset('admin') }}/assets/js/bundle/apexcharts.bundle.js"></script>
<script>
    window.PgCharts = (function () {
        var palette = @json($palette);
        var t = @json(['empty' => __('payment_gateways.chart.empty'), 'currency' => __('payment_gateways.chart.currency')]);
        var money = function (value) {
            return Number(value || 0).toLocaleString('en-US', { maximumFractionDigits: 2 }) + ' ' + t.currency;
        };
        var base = function (height, type) {
            return {
                chart: { type: type, height: height, toolbar: { show: false }, fontFamily: 'inherit', animations: { speed: 450 }, zoom: { enabled: false } },
                colors: palette,
                noData: { text: t.empty },
                grid: { borderColor: '#eef2f7', strokeDashArray: 4 },
                legend: { position: 'top', horizontalAlign: 'center', fontSize: '13px', markers: { radius: 12 } },
                dataLabels: { enabled: false },
                tooltip: { y: { formatter: money } }
            };
        };
        var mount = function (id, options) {
            var el = document.getElementById(id);
            if (el) new ApexCharts(el, options).render();
        };
        var isEmpty = function (series) {
            return !series.some(function (s) { return (s.data || [s]).some(function (v) { return Number(v) > 0; }); });
        };

        return {
            /** مساحات متدرجة للاتجاه الشهري */
            area: function (id, cfg, height) {
                var o = base(height || 330, 'area');
                o.series = isEmpty(cfg.series) ? [] : cfg.series;
                o.xaxis = { categories: cfg.categories, labels: { rotate: -35, style: { fontSize: '11px' } } };
                o.yaxis = { labels: { formatter: function (v) { return Number(v).toLocaleString('en-US', { maximumFractionDigits: 0 }); } } };
                o.stroke = { curve: 'smooth', width: 2.5 };
                o.fill = { type: 'gradient', gradient: { opacityFrom: .35, opacityTo: .02 } };
                o.tooltip.shared = true; o.tooltip.intersect = false;
                mount(id, o);
            },
            bar: function (id, cfg, height, opts) {
                opts = opts || {};
                var o = base(height || 300, 'bar');
                o.series = isEmpty(cfg.series) ? [] : cfg.series;
                o.xaxis = { categories: cfg.categories, labels: { rotate: -45, style: { fontSize: '10px' } } };
                o.yaxis = { labels: { formatter: function (v) { return Number(v).toLocaleString('en-US', { maximumFractionDigits: 0 }); } } };
                o.plotOptions = { bar: { borderRadius: 6, columnWidth: opts.width || '55%', distributed: !!opts.distributed } };
                if (opts.colors) o.colors = opts.colors;
                if (opts.distributed) o.legend = { show: false };
                mount(id, o);
            },
            donut: function (id, cfg, opts) {
                opts = opts || {};
                var o = base(300, 'donut');
                var total = cfg.series.reduce(function (a, b) { return a + Number(b); }, 0);
                o.series = total > 0 ? cfg.series : [];
                o.labels = cfg.labels;
                o.legend = { position: 'bottom', fontSize: '12px' };
                if (opts.colors) o.colors = opts.colors;
                o.tooltip = { y: { formatter: opts.count ? function (v) { return v; } : money } };
                o.plotOptions = { pie: { donut: { size: '68%', labels: { show: true,
                    value: { formatter: function (v) { return opts.count ? v : Number(v).toLocaleString('en-US', { maximumFractionDigits: 0 }); } },
                    total: { show: true, label: opts.totalLabel || '', formatter: function () {
                        return opts.count ? total : Number(total).toLocaleString('en-US', { maximumFractionDigits: 0 });
                    } } } } } };
                mount(id, o);
            }
        };
    })();
</script>
