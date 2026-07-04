  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">

     <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        @if($SystemSetting && $SystemSetting->frontend_community == 1 && app(\App\Services\CommunityRealtimeService::class)->moduleAvailable())
        <a href="{{route('timeline')}}" class="nav-link">Community Forum</a>
        @endif
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="#" class="nav-link"></a>
      </li>
      <li class="nav-item d-none d-md-inline-block">
        @php
          $packageContext = app(\App\Services\PackageGovernanceService::class)->context();
        @endphp
        <span class="nav-link">
          <span class="badge text-white" style="background-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }}; border-color: {{ $SystemSetting->dark_theme_color ?? '#008751' }};">
            {{ $packageContext['label'] }}
          </span>
        </span>
      </li>
    </ul>


    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Navbar Search -->
      <li class="nav-item">
        <a class="nav-link" data-widget="navbar-search" href="#" role="button">
          <i class="fas fa-search"></i>
        </a>
        <div class="navbar-search-block">
          <form class="form-inline">
            <div class="input-group input-group-sm">
              <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
              <div class="input-group-append">
                <button class="btn btn-navbar" type="submit">
                  <i class="fas fa-search"></i>
                </button>
                <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </form>
        </div>
      </li>

      @php
        $messageUnreadCount = 0;
        if (Auth::check()) {
            $messageUnreadCount = \App\Models\Conversation::whereHas('participants', function ($q) {
                    $q->where('user_id', Auth::id());
                })
                ->withCount([
                    'messages as unread_count' => function ($q) {
                        $q->whereNull('read_at')->where('sender_id', '!=', Auth::id());
                    },
                ])
                ->get()
                ->sum('unread_count');
        }
      @endphp
      <li class="nav-item" id="backend-message-bell" data-count-url="{{ route(auth()->user()->access_level.'.messages.unread-count') }}">
        <a class="nav-link" href="{{ route(auth()->user()->access_level.'.messages.page') }}" role="button" aria-label="Internal Communication">
          <i class="far fa-envelope"></i>
          <span class="badge badge-warning navbar-badge {{ $messageUnreadCount > 0 ? '' : 'd-none' }}" data-message-count>
            {{ $messageUnreadCount > 99 ? '99+' : $messageUnreadCount }}
          </span>
        </a>
      </li>

      <li class="nav-item dropdown" id="campaign-notification-bell"
          data-user-id="{{ Auth::id() }}"
          data-list-url="{{ route(auth()->user()->access_level.'.campaign.notifications.index') }}"
          data-count-url="{{ route(auth()->user()->access_level.'.campaign.notifications.unread') }}"
          data-read-url-template="{{ route(auth()->user()->access_level.'.campaign.notifications.read', ['notification' => '__ID__']) }}"
          data-read-all-url="{{ route(auth()->user()->access_level.'.campaign.notifications.readAll') }}">
        <a class="nav-link" data-toggle="dropdown" href="#" role="button" aria-label="Campaign notifications">
          <i class="far fa-bell"></i>
          <span class="badge badge-danger navbar-badge d-none" data-notification-count>0</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right campaign-notification-menu">
          <span class="dropdown-item dropdown-header" data-notification-header>No unread notifications</span>
          <div class="dropdown-divider"></div>
          <div data-notification-list>
            <span class="dropdown-item text-muted small">Loading notifications...</span>
          </div>
          <div class="dropdown-divider"></div>
          <button type="button" class="dropdown-item dropdown-footer text-center" data-notification-read-all>
            Mark all as read
          </button>
        </div>
      </li>

      <li class="nav-item">
        <a class="nav-link" data-widget="control-sidebar" data-controlsidebar-slide="true" href="#" role="button">
          <i class="fas fa-th-large"></i>
        </a>
      </li>

    </ul>
  </nav>
  <!-- /.navbar -->

  <style>
    .campaign-notification-menu {
      max-width: 360px;
      width: 360px;
    }

    .campaign-notification-item {
      white-space: normal;
    }

    .campaign-notification-title {
      color: #212529;
      font-weight: 600;
      line-height: 1.2;
      overflow-wrap: anywhere;
    }

    .campaign-notification-message {
      color: #6c757d;
      font-size: 0.85rem;
      line-height: 1.25;
      margin-top: 2px;
      overflow-wrap: anywhere;
    }

    .campaign-notification-meta {
      color: #888;
      font-size: 0.75rem;
      margin-top: 4px;
    }
  </style>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const root = document.getElementById('campaign-notification-bell');
      if (!root || !window.axios) {
        return;
      }

      const listUrl = root.dataset.listUrl;
      const countUrl = root.dataset.countUrl;
      const readUrlTemplate = root.dataset.readUrlTemplate;
      const readAllUrl = root.dataset.readAllUrl;
      const userId = root.dataset.userId;
      const countBadge = root.querySelector('[data-notification-count]');
      const header = root.querySelector('[data-notification-header]');
      const list = root.querySelector('[data-notification-list]');
      const readAll = root.querySelector('[data-notification-read-all]');

      const severityClass = {
        info: 'text-info',
        success: 'text-success',
        warning: 'text-warning',
        danger: 'text-danger'
      };

      function setCount(count) {
        const value = Number(count || 0);
        countBadge.textContent = value > 99 ? '99+' : value;
        countBadge.classList.toggle('d-none', value <= 0);
        header.textContent = value === 1 ? '1 unread notification' : value + ' unread notifications';
      }

      function timeAgo(value) {
        if (!value) {
          return '';
        }
        const seconds = Math.max(0, Math.floor((Date.now() - new Date(value).getTime()) / 1000));
        if (seconds < 60) return 'just now';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return minutes + 'm ago';
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return hours + 'h ago';
        return Math.floor(hours / 24) + 'd ago';
      }

      function render(items) {
        list.innerHTML = '';

        if (!items.length) {
          const empty = document.createElement('span');
          empty.className = 'dropdown-item text-muted small';
          empty.textContent = 'No unread notifications';
          list.appendChild(empty);
          return;
        }

        items.forEach(function (item) {
          const link = document.createElement('a');
          link.href = item.action_url || '#';
          link.className = 'dropdown-item campaign-notification-item';
          link.dataset.notificationId = item.id;
          link.dataset.actionUrl = item.action_url || '';

          const title = document.createElement('div');
          title.className = 'campaign-notification-title';
          const icon = document.createElement('i');
          icon.className = 'fas fa-circle mr-1 ' + (severityClass[item.severity] || 'text-info');
          icon.style.fontSize = '0.55rem';
          title.appendChild(icon);
          title.appendChild(document.createTextNode(item.title || 'Notification'));

          const message = document.createElement('div');
          message.className = 'campaign-notification-message';
          message.textContent = item.message || '';

          const meta = document.createElement('div');
          meta.className = 'campaign-notification-meta';
          meta.textContent = timeAgo(item.occurred_at);

          link.appendChild(title);
          link.appendChild(message);
          link.appendChild(meta);
          list.appendChild(link);
        });
      }

      function refresh() {
        window.axios.get(listUrl, { params: { unread: 1, limit: 8 } })
          .then(function (response) {
            render(response.data.data || []);
            setCount(response.data.unread_count || 0);
          });
      }

      list.addEventListener('click', function (event) {
        const item = event.target.closest('[data-notification-id]');
        if (!item) {
          return;
        }

        event.preventDefault();
        const url = readUrlTemplate.replace('__ID__', item.dataset.notificationId);
        window.axios.post(url).then(function (response) {
          setCount(response.data.unread_count || 0);
          item.remove();
          if (!list.querySelector('[data-notification-id]')) {
            render([]);
          }
          if (item.dataset.actionUrl) {
            window.location.href = item.dataset.actionUrl;
          }
        });
      });

      readAll.addEventListener('click', function () {
        window.axios.post(readAllUrl).then(function () {
          setCount(0);
          render([]);
        });
      });

      refresh();

      const messageBell = document.getElementById('backend-message-bell');
      if (messageBell) {
        const messageBadge = messageBell.querySelector('[data-message-count]');
        const messageCountUrl = messageBell.dataset.countUrl;
        const setMessageCount = function (count) {
          const value = Number(count || 0);
          messageBadge.textContent = value > 99 ? '99+' : value;
          messageBadge.classList.toggle('d-none', value <= 0);
        };
        const refreshMessageCount = function () {
          window.axios.get(messageCountUrl).then(function (response) {
            setMessageCount(response.data.unread || 0);
          }).catch(function () {});
        };
        setInterval(refreshMessageCount, 30000);
      }

      if (window.Echo && userId) {
        window.Echo.private('campaign.notifications.' + userId)
          .listen('.CampaignNotificationCreated', function (payload) {
            setCount(payload.unread_count || 0);
            refresh();
          });
      }
    });
  </script>
