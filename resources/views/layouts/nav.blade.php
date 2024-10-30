<ul class="navbar-nav" id="navbar-nav">
    <div id="two-column-menu">
    </div>

    <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-pages">Main
            Navigation</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link home" href="{{route('home')}}">
            Home
        </a>
    </li>

    @if(auth()->user()->roles == 'USER')
    @else
    <li class="nav-item">
        <a class="nav-link menu-link account" href="{{route('account')}}">
            Account
        </a>
    </li>   
    @endif

    
    <li class="nav-item">
        <a class="nav-link menu-link drive" href="{{route('drive')}}">
            My Drive
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link shared" href="{{route('shared')}}">
            Shared with Me
        </a>
    </li>
    
    <li class="nav-item">
        <a class="nav-link menu-link shared" href="{{route('account.profile')}}">
            Profile
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link" href="{{ route('logout') }}"
            onclick="event.preventDefault();document.getElementById('logout-form').submit();">
            Logout
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
            @honeypot
        </form>
    </li>
</ul>
