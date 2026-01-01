<aside :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'"
    class="sidebar fixed top-0 left-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-auto border-r border-gray-200 bg-white px-5 transition-all duration-300 lg:static lg:translate-x-0 dark:border-gray-800 dark:bg-black"
    @click.outside="sidebarToggle = false">
    <!-- SIDEBAR HEADER -->
    <div :class="sidebarToggle ? 'justify-center' : 'justify-between'"
        class="sidebar-header flex items-center gap-2 pt-8 pb-7">
        <a href="index.html">
            <span class="logo" :class="sidebarToggle ? 'hidden' : ''">
                <img class="dark:hidden" src="./images/logo/logo.svg" alt="Logo" />
                <img class="hidden dark:block" src="./images/logo/logo-dark.svg" alt="Logo" />
            </span>

            <img class="logo-icon" :class="sidebarToggle ? 'lg:block' : 'hidden'" src="./images/logo/logo-icon.svg"
                alt="Logo" />
        </a>
    </div>
    <!-- SIDEBAR HEADER -->

    <div class="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear">
        <!-- Sidebar Menu -->
        @inject('sidebarService', 'App\Services\Sidebar\SidebarService')
        @php
            $menus = $sidebarService::getSidebarMenu();
            $activeParent = $menus->first(function ($menu) {
                return $menu->is_active && $menu->children->isNotEmpty();
            });
            $initialSelected = $activeParent ? $activeParent->name : '';
        @endphp

        <nav x-data="{ selected: '{{ $initialSelected }}' }">
            <!-- Menu Group -->
            <div>
                <h3 class="mb-4 text-xs leading-[20px] text-gray-400 uppercase">
                    <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">
                        MENU
                    </span>

                    <svg :class="sidebarToggle ? 'lg:block hidden' : 'hidden'"
                        class="menu-group-icon mx-auto fill-current" width="24" height="24" viewBox="0 0 24 24"
                        fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                            fill="" />
                    </svg>
                </h3>

                <ul class="mb-6 flex flex-col gap-4">
                    @foreach ($menus as $menu)
                        @if ($menu->children->isNotEmpty())
                            <!-- Dropdown Menu Item -->
                            <li>
                                <a href="#"
                                    @click.prevent="selected = (selected === '{{ $menu->name }}' ? '':'{{ $menu->name }}')"
                                    class="menu-item group"
                                    :class="(selected === '{{ $menu->name }}') || {{ $menu->is_active ? 'true' : 'false' }} ?
                                        'menu-item-active' : 'menu-item-inactive'">

                                    @if ($menu->icon)
                                        <i class="{{ $menu->icon }} text-2xl"></i>
                                    @endif

                                    <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                        {{ $menu->name }}
                                    </span>

                                    <svg class="menu-item-arrow"
                                        :class="[(selected === '{{ $menu->name }}') ? 'menu-item-arrow-active' :
                                            'menu-item-arrow-inactive',
                                            sidebarToggle ? 'lg:hidden' : ''
                                        ]"
                                        width="20" height="20" viewBox="0 0 20 20" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke=""
                                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </a>

                                <!-- Dropdown Menu Start -->
                                <div class="translate transform overflow-hidden"
                                    :class="(selected === '{{ $menu->name }}') ? 'block' : 'hidden'">
                                    <ul :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                        class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                                        @foreach ($menu->children as $child)
                                            <li>
                                                <a href="{{ $child->route ? route($child->route) : $child->url ?? '#' }}"
                                                    class="menu-dropdown-item group {{ $child->is_active ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                                    {{ $child->name }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                <!-- Dropdown Menu End -->
                            </li>
                        @else
                            <!-- Simple Menu Item -->
                            <li>
                                <a href="{{ $menu->route ? route($menu->route) : $menu->url ?? '#' }}"
                                    class="menu-item group {{ $menu->is_active ? 'menu-item-active' : 'menu-item-inactive' }}">
                                    @if ($menu->icon)
                                        <i class="{{ $menu->icon }} text-2xl"></i>
                                    @endif

                                    <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                        {{ $menu->name }}
                                    </span>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </nav>
        <!-- Sidebar Menu -->

        <!-- Promo Box -->
        <div :class="sidebarToggle ? 'lg:hidden' : ''"
            class="mx-auto mb-10 w-full max-w-60 rounded-2xl bg-gray-50 px-4 py-5 text-center dark:bg-white/3">
            <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">
                #1 Tailwind CSS Dashboard
            </h3>
            <p class="text-theme-sm mb-4 text-gray-500 dark:text-gray-400">
                Leading Tailwind CSS Admin Template with 400+ UI Component and Pages.
            </p>
            <a href="https://tailadmin.com/pricing" target="_blank" rel="nofollow"
                class="bg-brand-500 text-theme-sm hover:bg-brand-600 flex items-center justify-center rounded-lg p-3 font-medium text-white">
                Purchase Plan
            </a>
        </div>
        <!-- Promo Box -->
    </div>
</aside>
