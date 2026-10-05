import { AnimatePresence, motion } from 'framer-motion'
import {
    Search,
    UserRound,
    ShoppingBag,
    X,
    ShieldCheck,
    ChevronDown,
    Sparkles,
    Flame,
    Percent,
    ArrowRight,
    Layers,
    Tag
} from 'lucide-react'
import { Link, useNavigate } from 'react-router-dom'
import { useEffect, useState, useMemo, useRef } from 'react'
import { useApp } from '../context/AppContext'
import { fetchCategories, fetchBrands } from '../services/catalog'
import type { CategoryItem, BrandItem } from '../types'

const TRENDING_SEARCHES = ['Phantom GX', 'Predator Accuracy', 'Áo đấu Dri-FIT', 'Bóng Match Pro'];
export function Header() {
    const { user, cartCount, cartPulse, setCartDrawerOpen, logout } = useApp()
    const [scrolled, setScrolled] = useState(false)
    const [searchOpen, setSearchOpen] = useState(false)
    const [accountOpen, setAccountOpen] = useState(false)
    const [search, setSearch] = useState('')
    const [activeDropdown, setActiveDropdown] = useState<string | null>(null)
    const hoverTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null)
    const navigate = useNavigate()
    const [categoriesList, setCategoriesList] = useState<CategoryItem[]>([])
    const [brandsList, setBrandsList] = useState<BrandItem[]>([])

    // Load dynamic categories & brands from API on mount
    useEffect(() => {
        let active = true
        Promise.all([fetchCategories(), fetchBrands()])
            .then(([cats, brands]) => {
                if (!active) return
                if (Array.isArray(cats)) setCategoriesList(cats)
                if (Array.isArray(brands)) setBrandsList(brands)
            })
            .catch(console.error)
        return () => {
            active = false
        }
    }, [])

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 20)
        window.addEventListener('scroll', onScroll)
        return () => window.removeEventListener('scroll', onScroll)
    }, [])

    const handleMouseEnter = (menuKey: string) => {
        if (hoverTimeoutRef.current) clearTimeout(hoverTimeoutRef.current)
        setActiveDropdown(menuKey)
    }

    const handleMouseLeave = () => {
        hoverTimeoutRef.current = setTimeout(() => {
            setActiveDropdown(null)
        }, 150)
    }

    const submit = (event: React.FormEvent) => {
        event.preventDefault()
        const term = search.trim()
        if (term) navigate(`/shop?search=${encodeURIComponent(term)}`)
        setSearchOpen(false)
    }

    const handleLogout = () => {
        logout()
        setAccountOpen(false)
        navigate('/')
    }

    // Primary top categories to show directly in navbar
    const primaryCategories = useMemo(() => {
        return categoriesList.filter((c) => c.name.toLowerCase() !== 'khác').slice(0, 4)
    }, [categoriesList])

    return (

        <header
            className={`sticky top-0 z-40 border-b transition-all duration-300 ${scrolled
                ? 'border-white/10 bg-[#0B0E17]/90 shadow-2xl shadow-emerald-950/30 backdrop-blur-md'
                : 'border-white/5 bg-[#0B0E17]/75 backdrop-blur-md'
                }`}
        >
            <div className="mx-auto flex h-[72px] max-w-7xl items-center justify-between px-5 lg:px-8">
                {/* Logo */}
                <Link
                    to="/"
                    aria-label="Về trang chủ Striker"
                    className="group flex cursor-pointer items-center gap-2 text-lg font-black tracking-[.16em] text-white transition-opacity hover:opacity-80"
                >
                    <span className="grid h-9 w-9 place-items-center rounded-xl rounded-bl-sm bg-lime-400 text-xl italic text-slate-950 transition-transform group-hover:rotate-12">
                        S
                    </span>
                    STRIKER<span className="text-lime-400">.</span>
                </Link>

                {/* Dynamic Desktop Navigation with Hover Dropdowns */}
                <nav className="hidden items-center gap-1 text-xs font-bold uppercase tracking-wider text-slate-400 md:flex">
                    {/* 1. Shop Mega Dropdown */}
                    <div
                        className="relative py-6"
                        onMouseEnter={() => handleMouseEnter('SHOP')}
                        onMouseLeave={handleMouseLeave}
                    >
                        <Link
                            to="/shop"
                            className={`flex items-center gap-1 px-3 py-1.5 rounded-xl transition ${activeDropdown === 'SHOP' ? 'text-lime-400 bg-white/5' : 'hover:text-lime-300'
                                }`}
                        >
                            <span>Cửa hàng</span>
                            <ChevronDown size={13} className={`transition-transform duration-200 ${activeDropdown === 'SHOP' ? 'rotate-180 text-lime-400' : ''}`} />
                        </Link>

                        {/* Shop Mega Dropdown Menu */}
                        <AnimatePresence>
                            {activeDropdown === 'SHOP' && (
                                <motion.div
                                    initial={{ opacity: 0, y: 8, scale: 0.98 }}
                                    animate={{ opacity: 1, y: 0, scale: 1 }}
                                    exit={{ opacity: 0, y: 8, scale: 0.98 }}
                                    transition={{ duration: 0.15 }}
                                    className="absolute left-0 top-full -mt-2 w-[580px] rounded-2xl border border-white/10 bg-[#131823] p-6 shadow-2xl z-50 normal-case"
                                >
                                    <div className="grid grid-cols-2 gap-6">
                                        {/* Left: Dynamic Categories */}
                                        <div>
                                            <span className="text-[10px] font-mono font-bold uppercase tracking-widest text-emerald-400 flex items-center gap-1.5 mb-3">
                                                <Layers size={13} /> Danh mục sản phẩm
                                            </span>
                                            <div className="space-y-1">
                                                {categoriesList.map((cat) => (
                                                    <Link
                                                        key={cat.id}
                                                        to={`/shop?category=${encodeURIComponent(cat.name)}`}
                                                        onClick={() => setActiveDropdown(null)}
                                                        className="group flex items-center justify-between px-3 py-2 rounded-xl hover:bg-white/10 transition text-slate-300 hover:text-white"
                                                    >
                                                        <span className="text-xs font-semibold">{cat.name}</span>
                                                        <ArrowRight size={12} className="text-slate-500 opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 group-hover:text-lime-400 transition-all" />
                                                    </Link>
                                                ))}
                                                <Link
                                                    to="/shop"
                                                    onClick={() => setActiveDropdown(null)}
                                                    className="block px-3 py-2 text-xs font-bold text-lime-400 hover:underline pt-2"
                                                >
                                                    Xem tất cả sản phẩm →
                                                </Link>
                                            </div>
                                        </div>

                                        {/* Right: Featured Collections & Top Brands */}
                                        <div className="border-l border-white/10 pl-6 space-y-5">
                                            <div>
                                                <span className="text-[10px] font-mono font-bold uppercase tracking-widest text-emerald-400 flex items-center gap-1.5 mb-3">
                                                    <Sparkles size={13} /> Bộ sưu tập nổi bật
                                                </span>
                                                <div className="space-y-1.5">
                                                    <Link
                                                        to="/shop?sort=featured"
                                                        onClick={() => setActiveDropdown(null)}
                                                        className="flex items-center gap-2 px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold text-slate-200 hover:text-lime-300 transition"
                                                    >
                                                        <Flame size={14} className="text-amber-400" />
                                                        <span>Sản phẩm bán chạy nhất</span>
                                                    </Link>
                                                    <Link
                                                        to="/shop?sort=low"
                                                        onClick={() => setActiveDropdown(null)}
                                                        className="flex items-center gap-2 px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold text-slate-200 hover:text-lime-300 transition"
                                                    >
                                                        <Percent size={14} className="text-rose-400" />
                                                        <span>Đang khuyến mãi giá tốt</span>
                                                    </Link>
                                                </div>
                                            </div>

                                            {/* Top Brands quick pills */}
                                            <div>
                                                <span className="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-400 block mb-2">
                                                    Thương hiệu hàng đầu
                                                </span>
                                                <div className="flex flex-wrap gap-1.5">
                                                    {brandsList.slice(0, 5).map((b) => (
                                                        <Link
                                                            key={b.id}
                                                            to={`/shop?brand=${encodeURIComponent(b.name)}`}
                                                            onClick={() => setActiveDropdown(null)}
                                                            className="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white/5 hover:bg-lime-400 hover:text-slate-950 text-slate-300 transition"
                                                        >
                                                            {b.name}
                                                        </Link>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </motion.div>
                            )}
                        </AnimatePresence>
                    </div>

                    {/* 2. Dynamic Primary Categories with Hover Sub-Menus */}
                    {primaryCategories.map((cat) => {
                        const menuKey = `CAT_${cat.id}`
                        return (
                            <div
                                key={cat.id}
                                className="relative py-6"
                                onMouseEnter={() => handleMouseEnter(menuKey)}
                                onMouseLeave={handleMouseLeave}
                            >
                                <Link
                                    to={`/shop?category=${encodeURIComponent(cat.name)}`}
                                    className={`flex items-center gap-1 px-3 py-1.5 rounded-xl transition ${activeDropdown === menuKey ? 'text-lime-400 bg-white/5' : 'hover:text-lime-300'
                                        }`}
                                >
                                    <span>{cat.name}</span>
                                    <ChevronDown size={13} className={`transition-transform duration-200 ${activeDropdown === menuKey ? 'rotate-180 text-lime-400' : ''}`} />
                                </Link>

                                {/* Category Hover Sub-Menu */}
                                <AnimatePresence>
                                    {activeDropdown === menuKey && (
                                        <motion.div
                                            initial={{ opacity: 0, y: 8, scale: 0.98 }}
                                            animate={{ opacity: 1, y: 0, scale: 1 }}
                                            exit={{ opacity: 0, y: 8, scale: 0.98 }}
                                            transition={{ duration: 0.15 }}
                                            className="absolute left-0 top-full -mt-2 w-72 rounded-2xl border border-white/10 bg-[#131823] p-4 shadow-2xl z-50 normal-case"
                                        >
                                            <div className="pb-2.5 mb-2.5 border-b border-white/10">
                                                <b className="block text-xs font-bold text-white">{cat.name}</b>
                                                <p className="text-[11px] text-slate-400 mt-0.5 leading-snug">
                                                    {cat.description || `Khám phá các sản phẩm ${cat.name.toLowerCase()} chính hãng chất lượng cao.`}
                                                </p>
                                            </div>

                                            <div className="space-y-1">
                                                <span className="text-[10px] font-mono uppercase tracking-wider text-slate-500 block px-2 mb-1">
                                                    Lọc theo thương hiệu:
                                                </span>
                                                {brandsList.slice(0, 4).map((brand) => (
                                                    <Link
                                                        key={brand.id}
                                                        to={`/shop?category=${encodeURIComponent(cat.name)}&brand=${encodeURIComponent(brand.name)}`}
                                                        onClick={() => setActiveDropdown(null)}
                                                        className="flex items-center justify-between px-3 py-1.5 rounded-xl text-xs text-slate-300 hover:text-white hover:bg-white/10 transition font-medium"
                                                    >
                                                        <span>{cat.name} {brand.name}</span>
                                                        <span className="text-[10px] font-mono text-lime-400">Xem</span>
                                                    </Link>
                                                ))}
                                            </div>

                                            <div className="mt-3 pt-2.5 border-t border-white/10">
                                                <Link
                                                    to={`/shop?category=${encodeURIComponent(cat.name)}`}
                                                    onClick={() => setActiveDropdown(null)}
                                                    className="flex items-center justify-between text-xs font-bold text-lime-400 hover:underline px-2"
                                                >
                                                    <span>Xem tất cả {cat.name}</span>
                                                    <ArrowRight size={13} />
                                                </Link>
                                            </div>
                                        </motion.div>
                                    )}
                                </AnimatePresence>
                            </div>
                        )
                    })}

                    {/* 3. Brands Dropdown */}
                    <div
                        className="relative py-6"
                        onMouseEnter={() => handleMouseEnter('BRANDS')}
                        onMouseLeave={handleMouseLeave}
                    >
                        <button
                            type="button"
                            className={`flex items-center gap-1 px-3 py-1.5 rounded-xl transition cursor-pointer ${activeDropdown === 'BRANDS' ? 'text-lime-400 bg-white/5' : 'hover:text-lime-300'
                                }`}
                        >
                            <span>Thương hiệu</span>
                            <ChevronDown size={13} className={`transition-transform duration-200 ${activeDropdown === 'BRANDS' ? 'rotate-180 text-lime-400' : ''}`} />
                        </button>

                        <AnimatePresence>
                            {activeDropdown === 'BRANDS' && (
                                <motion.div
                                    initial={{ opacity: 0, y: 8, scale: 0.98 }}
                                    animate={{ opacity: 1, y: 0, scale: 1 }}
                                    exit={{ opacity: 0, y: 8, scale: 0.98 }}
                                    transition={{ duration: 0.15 }}
                                    className="absolute left-0 top-full -mt-2 w-64 rounded-2xl border border-white/10 bg-[#131823] p-3 shadow-2xl z-50 normal-case"
                                >
                                    <span className="text-[10px] font-mono font-bold uppercase tracking-wider text-emerald-400 px-3 py-1 block">
                                        <Tag size={12} className="inline mr-1" /> Thương hiệu chính hãng
                                    </span>
                                    <div className="mt-1 space-y-0.5">
                                        {brandsList.map((brand) => (
                                            <Link
                                                key={brand.id}
                                                to={`/shop?brand=${encodeURIComponent(brand.name)}`}
                                                onClick={() => setActiveDropdown(null)}
                                                className="flex items-center justify-between px-3 py-2 rounded-xl text-xs text-slate-200 hover:text-white hover:bg-white/10 transition font-semibold"
                                            >
                                                <span>{brand.name}</span>
                                                <ArrowRight size={12} className="text-slate-500" />
                                            </Link>
                                        ))}
                                    </div>
                                </motion.div>
                            )}
                        </AnimatePresence>
                    </div>
                </nav>

                {/* Right Action Icons */}
                <div className="flex items-center gap-1 text-white">
                    <button
                        aria-label="Tìm kiếm"
                        onClick={() => setSearchOpen(true)}
                        className="rounded-xl p-3 transition hover:bg-white/10 hover:text-lime-300 cursor-pointer"
                    >
                        <Search size={19} />
                    </button>

                    {/* User Account / Auth Actions */}
                    {user ? (
                        <div
                            className="relative py-2"
                            onMouseEnter={() => {
                                if (hoverTimeoutRef.current) clearTimeout(hoverTimeoutRef.current)
                                setAccountOpen(true)
                            }}
                            onMouseLeave={() => {
                                hoverTimeoutRef.current = setTimeout(() => {
                                    setAccountOpen(false)
                                }, 200)
                            }}
                        >
                            <button
                                aria-label="Tài khoản"
                                onClick={() => setAccountOpen((open) => !open)}
                                className={`rounded-xl p-3 transition hover:bg-white/10 hover:text-lime-300 cursor-pointer ${accountOpen ? 'text-lime-400 bg-white/5' : ''
                                    }`}
                            >
                                <UserRound size={19} />
                            </button>

                            {/* Account Dropdown Menu */}
                            <AnimatePresence>
                                {accountOpen && (
                                    <motion.div
                                        initial={{ opacity: 0, y: -8, scale: 0.98 }}
                                        animate={{ opacity: 1, y: 0, scale: 1 }}
                                        exit={{ opacity: 0, y: -8 }}
                                        className="absolute right-0 top-full mt-2 w-64 rounded-2xl border border-white/10 bg-[#131823] p-2 text-sm text-white shadow-2xl z-50 normal-case"
                                    >
                                        <div className="mb-1 flex items-center gap-3 rounded-xl bg-white/5 p-3">
                                            <span className="grid h-9 w-9 place-items-center rounded-full bg-lime-400 font-black text-slate-950">
                                                {user.name.slice(0, 1).toUpperCase()}
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <b className="block text-sm font-bold text-white truncate">{user.name}</b>
                                                <span
                                                    className={`inline-flex items-center gap-1 text-[11px] font-bold font-mono ${user.role === 'admin' ? 'text-lime-400' : 'text-slate-400'
                                                        }`}
                                                >
                                                    {user.role === 'admin' && <ShieldCheck size={13} className="text-lime-400" />}
                                                    {user.role === 'admin' ? 'Quản Trị Viên' : 'Khách hàng'}
                                                </span>
                                            </div>
                                        </div>

                                        {user.role === 'admin' && (
                                            <Link
                                                onClick={() => setAccountOpen(false)}
                                                className="flex items-center gap-2.5 rounded-xl bg-gradient-to-r from-lime-400 to-emerald-400 px-3.5 py-2.5 text-xs font-black text-slate-950 shadow-lg shadow-lime-400/20 hover:brightness-110 transition my-1.5"
                                                to="/admin"
                                            >
                                                <ShieldCheck size={16} />
                                                <span>Trang Quản Trị Admin</span>
                                            </Link>
                                        )}

                                        <Link
                                            onClick={() => setAccountOpen(false)}
                                            className="block rounded-xl px-3 py-2.5 hover:bg-white/10 transition text-slate-200 hover:text-white"
                                            to="/profile"
                                        >
                                            Hồ sơ cá nhân
                                        </Link>
                                        <Link
                                            onClick={() => setAccountOpen(false)}
                                            className="block rounded-xl px-3 py-2.5 hover:bg-white/10 transition text-slate-200 hover:text-white"
                                            to="/orders"
                                        >
                                            Đơn hàng của tôi
                                        </Link>

                                        <div className="my-1 border-t border-white/10" />

                                        <button
                                            onClick={handleLogout}
                                            className="w-full rounded-xl px-3 py-2.5 text-left text-rose-400 hover:bg-white/10 transition font-medium cursor-pointer"
                                        >
                                            Đăng xuất
                                        </button>
                                    </motion.div>
                                )}
                            </AnimatePresence>
                        </div>
                    ) : (
                        <div className="flex items-center gap-2 pl-2">
                            <Link
                                to="/login"
                                className="flex items-center gap-1.5 rounded-xl border border-white/10 bg-white/5 px-3.5 py-2 text-xs font-bold text-slate-200 hover:border-lime-400/50 hover:bg-white/10 hover:text-lime-400 transition cursor-pointer"
                            >Đăng nhập
                                <UserRound size={19} />
                            </Link>
                        </div>
                    )}

                    <motion.button
                        animate={cartPulse ? { scale: [1, 1.22, 0.92, 1] } : { scale: 1 }}
                        aria-label="Giỏ hàng"
                        onClick={() => setCartDrawerOpen(true)}
                        className="relative rounded-xl p-3 transition hover:bg-white/10 hover:text-lime-300 cursor-pointer"
                    >
                        <ShoppingBag size={19} />
                        {cartCount > 0 && (
                            <span className="absolute right-1 top-1 grid h-4 min-w-4 place-items-center rounded-full bg-lime-400 px-1 text-[9px] font-black text-slate-950">
                                {cartCount}
                            </span>
                        )}
                    </motion.button>
                </div>
            </div>

            {/* Search Drawer */}
            <AnimatePresence>
                {searchOpen && (
                    <motion.div
                        initial={{ opacity: 0, y: -15 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: -15 }}
                        className="absolute left-0 right-0 top-full border-b border-white/10 bg-[#131823] px-5 py-5 shadow-2xl z-50"
                    >
                        <form
                            onSubmit={submit}
                            className="mx-auto flex max-w-3xl items-center gap-3 rounded-2xl border border-emerald-400/30 bg-white/10 px-4 py-3"
                        >
                            <Search size={20} className="text-emerald-300" />
                            <input
                                autoFocus
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Tìm giày, áo đấu, bóng, phụ kiện..."
                                className="min-w-0 flex-1 bg-transparent text-sm text-white outline-none placeholder:text-slate-500"
                            />
                            <button type="button" onClick={() => setSearchOpen(false)} className="cursor-pointer text-slate-400 hover:text-white">
                                <X size={18} />
                            </button>
                        </form>
                        <div className="mx-auto mt-3 flex max-w-3xl flex-wrap gap-2 text-xs text-slate-400">
                            <span className="mr-2 py-2">Tìm kiếm nổi bật:</span>
                            {TRENDING_SEARCHES.map((item) => (
                                <button
                                    type="button"
                                    onClick={() => {
                                        setSearch(item)
                                        navigate(`/shop?search=${encodeURIComponent(item)}`)
                                        setSearchOpen(false)
                                    }}
                                    key={item}
                                    className="rounded-full border border-white/10 px-3 py-1.5 transition hover:border-lime-400/50 hover:text-lime-300 cursor-pointer"
                                >
                                    {item}
                                </button>
                            ))}
                        </div>
                    </motion.div>
                )}
            </AnimatePresence>
        </header>
    )
}