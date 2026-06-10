@if(!empty($statsEndpoint))
<script>
    (function ($) {
        if (!$ || !window.setTimeout) {
            return;
        }

        var endpoint = @json($statsEndpoint);
        var baseInterval = {{ (int) ($pollInterval ?? 30000) }};
        var currentInterval = baseInterval;
        var maxInterval = 120000;
        var timer = null;
        var requestActive = false;

        function updateCard(group, card) {
            if (!card || !card.key) {
                return;
            }

            var selector = '[data-dashboard-stat-card][data-dashboard-stat-group="' + group + '"][data-dashboard-stat-key="' + card.key + '"]';
            var $card = $(selector);

            if (!$card.length) {
                return;
            }

            $card.find('[data-stat-value]').text(card.display_value);
            $card.find('[data-stat-suffix]').text(card.suffix || '');

            if (card.display_total !== null && typeof card.display_total !== 'undefined') {
                $card.find('[data-stat-total]').text(card.display_total);
            }

            if (card.percent !== null && typeof card.percent !== 'undefined') {
                $card.find('[data-stat-percent]').text(card.percent);
            }
        }

        function scheduleNext() {
            clearTimeout(timer);

            if (document.hidden) {
                timer = setTimeout(scheduleNext, baseInterval);
                return;
            }

            timer = setTimeout(fetchStats, currentInterval);
        }

        function fetchStats() {
            if (requestActive || document.hidden) {
                scheduleNext();
                return;
            }

            requestActive = true;

            $.ajax({
                url: endpoint,
                method: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function (response) {
                    var groups = response.card_groups || {};

                    Object.keys(groups).forEach(function (group) {
                        (groups[group] || []).forEach(function (card) {
                            updateCard(group, card);
                        });
                    });

                    currentInterval = baseInterval;
                },
                error: function () {
                    currentInterval = Math.min(currentInterval * 2, maxInterval);
                },
                complete: function () {
                    requestActive = false;
                    scheduleNext();
                }
            });
        }

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                currentInterval = baseInterval;
                clearTimeout(timer);
                fetchStats();
            }
        });

        scheduleNext();
    })(window.jQuery);
</script>
@endif
