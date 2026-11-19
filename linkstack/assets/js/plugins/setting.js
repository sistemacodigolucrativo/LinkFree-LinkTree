/*
* Version: 1.2.0
* Template: Hope-Ui - Responsive Bootstrap 5 Admin Dashboard Template
* Author: iqonic.design
* Design and Developed by: iqonic.design
* NOTE: This file contains all the setting change script.
*/

/*----------------------------------------------
Index Of Script
------------------------------------------------

:: Variables

------- Functions --------
:: rtlModeDefault - This function help for change offcanvas position based on rtlMode
:: checkSettingMenu - This function help for on page load setting panel active
:: darkMode - This function help for change dark mode
:: changeMode -This function help for change with create event
:: updateMode - This function help for change dashboard setting on page load based on session storage

------- Listners --------
:: RTL Mode
:: Color Mode
:: Sidebar Color
:: Sidebar Types
:: Sidebar Active Style
:: Navbar & Header style
:: Navbar default style
:: colorChange Mode

------------------------------------------------
Index Of Script
----------------------------------------------*/

(function () {
    "use strict";

    // Variables
    let sidebarTypeSetting = [];

    //  RTL mode on change offcanvas position change function
    const rtlModeDefault = (check) => {
        if (check) {
            $('.offcanvas-start').addClass('on-rtl start').removeClass('offcanvas-start')
            $('.offcanvas-end').addClass('on-rtl end').removeClass('offcanvas-end')
            $('.on-rtl.start').addClass('offcanvas-end').removeClass('start')
            $('.on-rtl.end').addClass('offcanvas-start').removeClass('end')
        } else {
            $('.offcanvas-start').addClass('on-rtl start').removeClass('offcanvas-start')
            $('.offcanvas-end').addClass('on-rtl end').removeClass('offcanvas-end')
            $('.on-rtl.start').addClass('offcanvas-end').removeClass('start')
            $('.on-rtl.end').addClass('offcanvas-start').removeClass('end')
        }
    }

    // On Page Load Active Function
    const checkSettingMenu = (type, name, value) => {
        const items = document.querySelectorAll('[data-setting="' + type + '"][data-name="' + name + '"]');
        items.forEach(el => el.classList.toggle('active', el.getAttribute('data-value') === value));
    }

        // Dark mode enable & disabled function
    const darkMode = () => {
        if (document.querySelector('body').classList.contains('auto')) {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.querySelector('body').classList.add('dark')
            } else {
                document.querySelector('body').classList.remove('dark')
            }
        }
    }

    // For Dark, RTL & Sidebar Class Update
    const changeMode = (type, value, target) => {
        let detailObj = {}
        if (type == 'color-mode') {
            detailObj = {dark: value}
            document.querySelector('body').classList.add(value)
        }
        if (type == 'dir-mode') {
            detailObj = {dir: value}
            document.querySelector('html').setAttribute('dir',value)
        }
        if (type == 'sidebar') {
            detailObj = {'sidebar-color': value}            
           const sidebarclass= document.querySelector('.sidebar-default')
           if(sidebarclass !== null && sidebarclass !== undefined){
                sidebarclass.classList.add(value)
           }
        }
        const event = new CustomEvent("ChangeMode", {detail: detailObj });
        document.dispatchEvent(event);
    }

    // Restauracao deterministica das preferencias no mesmo navegador, para ambos os paineis.
    const sidebar = document.querySelector('.sidebar-default');
    const colorModes = ['auto', 'dark', 'light'];
    const sidebarColorsValues = ['sidebar-white', 'sidebar-dark', 'sidebar-color', 'sidebar-transparent'];
    const sidebarStyles = ['navs-rounded', 'navs-rounded-all', 'navs-pill', 'navs-pill-all'];
    const sidebarTypesValues = ['sidebar-mini', 'sidebar-hover', 'sidebar-boxed'];

    const safeReadTypes = () => {
        try {
            const parsed = JSON.parse(localStorage.getItem('sidebarType') || '[]');
            return Array.isArray(parsed) ? parsed.filter(type => sidebarTypesValues.includes(type)) : [];
        } catch (_) {
            return [];
        }
    };
    const applyColorMode = value => {
        const chosen = colorModes.includes(value) ? value : 'auto';
        document.body.classList.remove(...colorModes);
        document.body.classList.add(chosen);
        if (chosen === 'auto') {
            document.body.classList.toggle('dark', !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches));
        }
        checkSettingMenu('color-mode', 'color', chosen);
    };
    const applySidebarColor = value => {
        if (!sidebar) return;
        const chosen = sidebarColorsValues.includes(value) ? value : 'sidebar-white';
        sidebar.classList.remove(...sidebarColorsValues);
        sidebar.classList.add(chosen);
        checkSettingMenu('sidebar', 'sidebar-color', chosen);
    };
    const applySidebarTypes = value => {
        if (!sidebar) return;
        const types = value.includes('sidebar-hover') ? ['sidebar-mini', 'sidebar-hover'] :
                      value.includes('sidebar-boxed') ? ['sidebar-boxed'] :
                      value.includes('sidebar-mini') ? ['sidebar-mini'] : [];
        sidebarTypeSetting = types;
        sidebar.classList.remove(...sidebarTypesValues);
        sidebar.classList.add(...types);
        document.querySelectorAll('[data-setting="sidebar"][data-name="sidebar-type"]').forEach(el => {
            el.classList.toggle('active', types.includes(el.getAttribute('data-value')));
        });
    };
    const applySidebarStyle = value => {
        if (!sidebar) return;
        const chosen = sidebarStyles.includes(value) ? value : 'navs-rounded-all';
        sidebar.classList.remove(...sidebarStyles);
        sidebar.classList.add(chosen);
        checkSettingMenu('sidebar', 'sidebar-item', chosen);
    };
    const updateMode = () => {
        applyColorMode(localStorage.getItem('color-mode') || 'auto');
        if (window.matchMedia) {
            const preference = window.matchMedia('(prefers-color-scheme: dark)');
            if (preference.addEventListener) preference.addEventListener('change', () => {
                if (localStorage.getItem('color-mode') === 'auto') applyColorMode('auto');
            });
        }
        const direction = localStorage.getItem('dir-mode');
        if (direction === 'rtl' || direction === 'ltr') {
            document.documentElement.setAttribute('dir', direction);
            checkSettingMenu('dir-mode', 'dir', direction);
        }
        applySidebarColor(localStorage.getItem('sidebar'));
        applySidebarTypes(safeReadTypes());
        applySidebarStyle(localStorage.getItem('sidebar-style'));

        const navbar = document.querySelector('.iq-navbar');
        const banner = document.querySelector('.iq-navbar-header');
        const navSetting = localStorage.getItem('navbarTypes');
        if (navbar && banner && navSetting) {
            navbar.classList.remove('nav-glass', 'navs-sticky', 'navs-transparent');
            banner.classList.remove('navs-bg-color');
            if (navSetting === 'navs-bg-color') banner.classList.add(navSetting);
            else if (['nav-glass', 'navs-sticky', 'navs-transparent'].includes(navSetting)) navbar.classList.add(navSetting);
            checkSettingMenu('navbar', 'navbar-type', navSetting);
        }
    };

    updateMode();

        // Modo de cor: alternancia imediata, estado selecionado e persistencia.
    const colorMode = document.querySelectorAll('[data-setting="color-mode"][data-name="color"]');
    colorMode.forEach(mode => mode.addEventListener('click', () => {
        const chosen = mode.getAttribute('data-value');
        if (!colorModes.includes(chosen)) return;
        localStorage.setItem('color-mode', chosen);
        applyColorMode(chosen);
        changeMode('color-mode', chosen);
    }));

        //rtl & ltr
    const dirMode = document.querySelectorAll('[data-setting="dir-mode"][data-name="dir"]')
    Array.from(dirMode, (mode) => {
        mode.addEventListener('click', (e) => {
            Array.from(dirMode, (el) => {
                el.classList.remove('active')
            })
            if (!mode.classList.contains('active')) {
                Array.from(document.querySelectorAll(`[data-value="${mode.getAttribute('data-value')}"]`), (el) => el.classList.add('active'))
                localStorage.setItem('dir-mode', mode.getAttribute('data-value'))
                changeMode('dir-mode', mode.getAttribute('data-value'))
            }
        })
    })
    
    //Sidebar Color
    const sidebarColors = document.querySelectorAll('[data-setting="sidebar"][data-name="sidebar-color"]')
    Array.from(sidebarColors, (mode) => {
        mode.addEventListener('click', (e) => {
            Array.from(sidebarColors, (el) => {
                el.classList.remove('active')
                document.querySelector('.sidebar-default').classList.remove(el.getAttribute('data-value'))
            })
            localStorage.setItem('sidebar', mode.getAttribute('data-value'))
            mode.classList.add('active')
            document.querySelector('.sidebar-default').classList.add(mode.getAttribute('data-value'))
            changeMode('sidebar', mode.getAttribute('data-value'))
        })
    })

    // Layout lateral: apenas uma modalidade por vez; Hover depende de Mini.
    const sidebarTypes = document.querySelectorAll('[data-setting="sidebar"][data-name="sidebar-type"]');
    sidebarTypes.forEach(type => type.addEventListener('click', () => {
        const selected = type.getAttribute('data-value');
        if (!sidebarTypesValues.includes(selected)) return;
        const next = sidebarTypeSetting.includes(selected) ? [] : [selected];
        applySidebarTypes(next);
        localStorage.setItem('sidebarType', JSON.stringify(sidebarTypeSetting));
    }));

        // Estilo dos itens ativos: atualiza toda a familia de classes e controles.
    const allActiveType = document.querySelectorAll('[data-setting="sidebar"][data-name="sidebar-item"]');
    allActiveType.forEach(option => option.addEventListener('click', () => {
        const chosen = option.getAttribute('data-value');
        if (!sidebarStyles.includes(chosen)) return;
        localStorage.setItem('sidebar-style', chosen);
        applySidebarStyle(chosen);
    }));

        // Navbar Style
    const allNavbarType = document.querySelectorAll('[data-setting="navbar"][data-name="navbar-type"]')
    Array.from(allNavbarType, (navbarType) => {
        navbarType.addEventListener('click', (e) => {
            Array.from(allNavbarType, (el) => {
                el.classList.remove('active')
                document.querySelector(el.getAttribute('data-target')).classList.remove(el.getAttribute('data-value'))
            })
            localStorage.setItem('navbarTypes', navbarType.getAttribute('data-value'))
            Array.from(document.querySelectorAll(`[data-value="${navbarType.getAttribute('data-value')}"]`), (el) => el.classList.add('active'))
            document.querySelector(navbarType.getAttribute('data-target')).classList.add(navbarType.getAttribute('data-value'))
            changeMode('navbarTypes', navbarType.getAttribute('data-value'), navbarType.getAttribute('data-target'))
        })
    })

    // Navbar default style
    const defaultNavbarType = document.querySelector('[data-setting="navbar"][data-name="navbar-default"]')
    if (defaultNavbarType !== null) {
        defaultNavbarType.addEventListener("click", (e) => {
            document.querySelector('.iq-navbar').classList.remove('nav-glass','navs-sticky', 'navs-transparent')
            document.querySelector('.iq-navbar-header').classList.remove('navs-bg-color')
            localStorage.setItem('navbarTypes', '')
        })
    }
   
    // For colorChange Mode
    const customizerMode = (custombodyclass,colors,colorInfo) => {
    document.querySelector('body').classList.add(`${custombodyclass}`)
    localStorage.setItem('colorcustomchart-mode', getComputedStyle(document.body).getPropertyValue('--bs-primary'))
    document.documentElement.style.setProperty('--bs-info', colors);
    const color = localStorage.getItem('colorcustomchart-mode')
    if(color !== 'null' && color !== undefined && color !== ''){
    const event = new CustomEvent("ColorChange", {detail :{detail1:color.trim(), detail2:colors.trim()}});
    document.dispatchEvent(event);
    }
    else{
    const event = new CustomEvent("ColorChange", {detail :{detail1:colorInfo.trim(), detail2:colors.trim()}});
    document.dispatchEvent(event);
    }
    const elements = document.querySelectorAll('[data-setting="color-mode1"][data-name="color"]')
    Array.from(elements, (mode) => {
        const colorclass = mode.getAttribute('data-value');
        if(colorclass === custombodyclass ){
            mode.classList.add('active')
        }
        else{
            mode.classList.remove('active')
        }
    })
    }

    const elements = document.querySelectorAll('[data-setting="color-mode1"][data-name="color"]')
    Array.from(elements, (mode) => {
    mode.addEventListener('click', (e) => {
        Array.from(elements, (el) => {
            el.classList.remove('active')
            document.querySelector('body').classList.remove(el.getAttribute('data-value'))
        })
        localStorage.setItem('colorcustom-mode', mode.getAttribute('data-value'))
        localStorage.setItem('colorcustominfo-mode', mode.getAttribute('data-info'))
        
        mode.classList.add('active')
        const colors = mode.getAttribute('data-info');
        const color = getComputedStyle(document.body).getPropertyValue('--bs-primary');
        customizerMode(mode.getAttribute('data-value'),colors,color)
        
        })
    })
    
    const custombodyclass = localStorage.getItem('colorcustom-mode')
    const colors = localStorage.getItem('colorcustominfo-mode')
    const color = localStorage.getItem('colorcustomchart-mode')
    if(custombodyclass !== null && custombodyclass !== undefined && colors !== null && colors !== undefined){
        customizerMode(custombodyclass,colors,color)     
    }

})()
