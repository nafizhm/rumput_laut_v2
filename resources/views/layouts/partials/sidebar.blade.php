<aside :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'"
    class="sidebar fixed top-0 left-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-auto border-r border-gray-200 bg-white px-5 transition-all duration-300 lg:static lg:translate-x-0 dark:border-gray-800 dark:bg-black"
    @click.outside="if (window.innerWidth < 1024) sidebarToggle = false">

    <div :class="sidebarToggle ? 'justify-center' : 'justify-between'"
        class="sidebar-header flex items-center gap-2 pt-8 pb-7">
        <a href="index.html" class="flex items-center">
            <span class="logo flex items-center" :class="sidebarToggle ? 'hidden' : ''">
                <span class="text-2xl font-black tracking-tighter text-gray-900 dark:text-white">
                    RLS<span class="text-brand-500">.</span>
                </span>
            </span>

            <span class="logo-icon hidden font-black text-brand-500 text-2xl"
                :class="sidebarToggle ? 'lg:block' : 'hidden'">
                R
            </span>
        </a>
    </div>

    <div class="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear">
        @inject('sidebarService', 'App\Services\Sidebar\SidebarService')
        @php
            $menus = $sidebarService::getSidebarMenu();
            $activeParent = $menus->first(function ($menu) {
                return $menu->is_active && $menu->children->isNotEmpty();
            });
            $initialSelected = $activeParent ? $activeParent->name : '';
        @endphp

        <nav x-data="{ selected: '{{ $initialSelected }}' }">
            <div>
                <h3 class="mb-4 text-xs leading-[20px] text-gray-400 uppercase">
                    <span class="menu-group-title" :class="sidebarToggle ? 'lg:hidden' : ''">
                        MENU
                    </span>

                    <svg :class="sidebarToggle ? 'lg:block hidden' : 'hidden'"
                        class="menu-group-icon mx-auto fill-current text-gray-400" width="24" height="24"
                        viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.7551 11.9991 13.7551C12.9656 13.7551 13.7491 12.9716 13.7491 12.0051V11.9951Z"
                            fill="currentColor" />
                    </svg>
                </h3>

                <ul class="mb-6 flex flex-col gap-1">
                    @foreach ($menus as $menu)
                        @if ($menu->children->isNotEmpty())
                            <li>
                                <a href="#"
                                    @click.prevent="selected = (selected === '{{ $menu->name }}' ? '':'{{ $menu->name }}')"
                                    class="menu-item group transition-colors duration-200"
                                    :class="(selected === '{{ $menu->name }}') || {{ $menu->is_active ? 'true' : 'false' }} ?
                                        'menu-item-active' : 'menu-item-inactive'">

                                    @if ($menu->icon)
                                        <i data-lucide="{{ $menu->icon }}"
                                            class="w-6 h-6 transition-colors duration-200"
                                            :class="(selected === '{{ $menu->name }}') ||
                                            {{ $menu->is_active ? 'true' : 'false' }} ? 'text-brand-500!' :
                                                'text-gray-500'"></i>
                                    @endif

                                    <span class="menu-item-text" :class="sidebarToggle ? 'lg:hidden' : ''">
                                        {{ $menu->name }}
                                    </span>

                                    <svg class="menu-item-arrow transition-transform duration-300 ease-in-out"
                                        :class="[(selected === '{{ $menu->name }}') ? 'rotate-180' : '',
                                            sidebarToggle ? 'lg:hidden' : ''
                                        ]"
                                        width="20" height="20" viewBox="0 0 20 20" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M4.79175 7.39584L10.0001 12.6042L15.2084 7.39585" stroke="currentColor"
                                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </a>

                                <div x-show="selected === '{{ $menu->name }}'" x-collapse x-cloak>
                                    <ul :class="sidebarToggle ? 'lg:hidden' : 'flex'"
                                        class="menu-dropdown mt-2 flex flex-col gap-1 pl-9">
                                        @foreach ($menu->children as $child)
                                            <li>
                                                <a href="{{ $child->route ? route($child->route) : $child->url ?? '#' }}"
                                                    class="menu-dropdown-item group transition-all duration-200 {{ $child->is_active ? 'menu-dropdown-item-active' : 'menu-dropdown-item-inactive' }}">
                                                    {{ $child->name }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </li>
                        @else
                            <li>
                                <a href="{{ $menu->route ? route($menu->route) : $menu->url ?? '#' }}"
                                    class="menu-item group transition-colors duration-200 {{ $menu->is_active ? 'menu-item-active' : 'menu-item-inactive' }}">
                                    @if ($menu->icon)
                                        <i data-lucide="{{ $menu->icon }}"
                                            class="w-6 h-6 transition-colors duration-200"
                                            :class="selected === '{{ $menu->name }}' ||
                                                {{ $menu->is_active ? 'true' : 'false' }} ? 'text-brand-500!' :
                                                'text-gray-500'"></i>
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
    </div>
</aside>
