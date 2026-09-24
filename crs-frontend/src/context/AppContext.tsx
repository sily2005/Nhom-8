/* eslint-disable react-refresh/only-export-components */
import { createContext, useContext, useEffect, useRef, useState, type ReactNode } from 'react'
import { toast } from 'sonner'
import { addCartItem, fetchOrders, mapBackendOrder, cancelOrder as apiCancelOrder } from '../services/orders'
import {
  fetchAddresses,
  createAddress as apiCreateAddress,
  updateAddress as apiUpdateAddress,
  deleteAddress as apiDeleteAddress,
  setDefaultAddress as apiSetDefaultAddress,
  mapDbAddress,
} from '../services/auth'
import { applyCoupon as apiApplyCoupon } from '../services/coupons'
import { calculateOrderTotals, BASE_SHIPPING_FEE } from '../data/coupons'
import type {
  Address,
  CartItem,
  Coupon,
  Order,
  OrderStatus,
  PaymentStatus,
  Product,
  Role,
  User,
} from '../types'

// ── Cart key helper ──────────────────────────────────────────────────────────
const getCartKey = (u: { id?: string | number } | null | undefined) =>
  u?.id ? `cart_${u.id}` : 'cart_guest'

const parseCartFromStorage = (key: string): CartItem[] => {
  const stored = localStorage.getItem(key)
  if (!stored) return []
  try {
    const items = JSON.parse(stored) as Partial<CartItem>[]
    return items.map((item) => {
      const resolvedImg =
        item.image ||
        (item as any).image_url ||
        (Array.isArray((item as any).images) && (item as any).images.length > 0 ? (item as any).images[0] : '') ||
        ''
      return {
        ...item,
        image: resolvedImg,
        cartItemId:
          item.cartItemId ??
          `${item.id}_${item.selectedSize ?? 'default'}_${item.selectedColor ?? 'default'}`,
        selected: item.selected !== false,
      }
    }) as CartItem[]
  } catch {
    return []
  }
}

// ── Coupon normalizer — maps backend coupon response → frontend Coupon type ──
function mapBackendCoupon(raw: Record<string, any>): Coupon {
  return {
    id: String(raw.id ?? raw.code),
    code: raw.code ?? '',
    title: raw.title ?? raw.code ?? '',
    description: raw.description ?? '',
    discountType: raw.type === 'fixed' ? 'fixed' : raw.type === 'freeship' ? 'freeship' : 'percent',
    discountValue: Number(raw.value ?? 0),
    minOrderValue: Number(raw.min_order_amount ?? 0),
    maxDiscount: raw.max_discount_amount ? Number(raw.max_discount_amount) : undefined,
    expiresAt: raw.expires_at ?? '',
    isActive: Boolean(raw.is_active ?? true),
  }
}

// ── Context type ─────────────────────────────────────────────────────────────
type AppContextValue = {
  user: User | null
  cart: CartItem[]
  isAuthenticated: boolean
  authModalOpen: boolean
  setAuthModalOpen: (open: boolean) => void
  login: (user: User, token?: string) => void
  logout: () => void
  updateUserProfile: (data: Partial<User>) => void
  addAddress: (address: Omit<Address, 'id'>) => Promise<Address | null>
  updateAddress: (id: string, address: Partial<Address>) => Promise<Address | null>
  deleteAddress: (id: string) => Promise<void>
  setDefaultAddress: (id: string) => Promise<void>
  addToCart: (product: Product, quantity?: number, options?: { size?: string; color?: string }) => void
  updateCart: (cartItemId: string, quantity: number) => void
  removeFromCart: (cartItemId: string) => void
  toggleCartItem: (cartItemId: string) => void
  toggleSelectAll: () => void
  updateCartVariant: (cartItemId: string, size: string, color: string) => void
  removePurchasedItems: (items: Array<{ id: number; selectedSize?: string; selectedColor?: string; cartItemId?: string }>) => void
  clearCart: () => void
  cartCount: number
  cartSubtotal: number
  cartTotal: number
  shippingFee: number
  discountAmount: number
  appliedCoupon: Coupon | null
  applyCoupon: (code: string) => Promise<{ success: boolean; message: string }>
  removeCoupon: () => void
  savedCoupons: string[]
  saveCoupon: (code: string) => void
  isCouponSaved: (code: string) => boolean
  orders: Order[]
  ordersLoading: boolean
  refreshOrders: () => Promise<void>
  addOrder: (order: Order) => void
  updateOrderStatus: (orderId: string, status: OrderStatus, paymentStatus?: PaymentStatus) => void
  cancelOrder: (orderId: string) => Promise<void>
  toast: string | null
  notify: (message: string) => void
  cartDrawerOpen: boolean
  setCartDrawerOpen: (open: boolean) => void
  cartPulse: boolean
}

const AppContext = createContext<AppContextValue | null>(null)

export function AppProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(() => {
    const stored = localStorage.getItem('crs_user')
    if (!stored) return null
    try {
      const parsed = JSON.parse(stored) as User & { phone_number?: string }
      if (!parsed.role) {
        parsed.role = 'user'
      }
      if (parsed.addresses && parsed.addresses.length > 0) {
        parsed.addresses = parsed.addresses.map((a: any) =>
          a.recipient_name ? mapDbAddress(a) : a
        )
      }
      localStorage.setItem('crs_user', JSON.stringify(parsed))
      localStorage.setItem('crs_role', parsed.role)
      return parsed
    } catch {
      localStorage.removeItem('crs_user')
      localStorage.removeItem('crs_role')
      return null
    }
  })

  // Thay vì dùng Modal pop-up cũ, chuyển hướng sang /login khi cần
  const setAuthModalOpen = (open: boolean) => {
    if (open) {
      window.location.href = '/login'
    }
  }

  // Cart state: scoped per user
  const [cart, setCart] = useState<CartItem[]>(() => {
    let initialUser: { id: string | number } | null = null
    try {
      const stored = localStorage.getItem('crs_user')
      if (stored) initialUser = JSON.parse(stored) as { id: string | number }
    } catch { /* ignore */ }
    return parseCartFromStorage(getCartKey(initialUser))
  })

  const cartKeyRef = useRef<string>(getCartKey(null))
  useEffect(() => { cartKeyRef.current = getCartKey(user) }, [user])

  const [appliedCoupon, setAppliedCoupon] = useState<Coupon | null>(() => {
    const stored = localStorage.getItem('crs_coupon')
    if (!stored) return null
    try { return JSON.parse(stored) as Coupon } catch { return null }
  })

  const [savedCoupons, setSavedCoupons] = useState<string[]>(() => {
    const stored = localStorage.getItem('crs_saved_coupons')
    if (!stored) return []
    try { return JSON.parse(stored) as string[] } catch { return [] }
  })

  const [orders, setOrders] = useState<Order[]>([])
  const [ordersLoading, setOrdersLoading] = useState(false)

  const refreshOrders = async () => {
    if (!user?.id) { setOrders([]); return }
    setOrdersLoading(true)
    try {
      const raw = await fetchOrders(user.id as number)
      const list: any[] = Array.isArray(raw) ? raw : (raw?.data ?? [])
      setOrders(list.map(mapBackendOrder))
    } catch {
      // Silent fail
    } finally {
      setOrdersLoading(false)
    }
  }

  useEffect(() => {
    void refreshOrders()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [user?.id])

  useEffect(() => {
    if (!user?.id) return
    fetchAddresses(user.id as number).then((addrs) => {
      setUser((prev) => {
        if (!prev) return null
        const updated = { ...prev, addresses: addrs }
        localStorage.setItem('crs_user', JSON.stringify(updated))
        return updated
      })
    }).catch(() => { /* silent */ })
  }, [user?.id])

  const [cartDrawerOpen, setCartDrawerOpen] = useState(false)
  const [cartPulse, setCartPulse] = useState(false)
  const [legacyToast, setLegacyToast] = useState<string | null>(null)

  useEffect(() => {
    localStorage.setItem(cartKeyRef.current, JSON.stringify(cart))
  }, [cart])

  useEffect(() => {
    if (appliedCoupon) {
      localStorage.setItem('crs_coupon', JSON.stringify(appliedCoupon))
    } else {
      localStorage.removeItem('crs_coupon')
    }
  }, [appliedCoupon])

  useEffect(() => {
    localStorage.setItem('crs_saved_coupons', JSON.stringify(savedCoupons))
  }, [savedCoupons])

  useEffect(() => {
    if (user) { localStorage.setItem('crs_user', JSON.stringify(user)) }
  }, [user])

  useEffect(() => {
    const unauthorized = () => setUser(null)
    window.addEventListener('crs:unauthorized', unauthorized)
    return () => window.removeEventListener('crs:unauthorized', unauthorized)
  }, [])

  const notify = (message: string) => {
    setLegacyToast(message)
    toast(message)
    window.setTimeout(() => setLegacyToast(null), 2800)
  }

  const login = (nextUser: User & { phone_number?: string; addresses?: any[] }, token = 'demo-token') => {
    const role: Role = nextUser.role === 'admin' ? 'admin' : (nextUser.role || 'user')

    const normalizedAddresses: Address[] = (nextUser.addresses ?? []).map((a: any) =>
      a.recipient_name ? mapDbAddress(a) : (a as Address)
    )

    const fullUser: User = {
      ...nextUser,
      role,
      addresses: normalizedAddresses,
    }
    localStorage.setItem('crs_token', token)
    localStorage.setItem('crs_user', JSON.stringify(fullUser))
    localStorage.setItem('crs_role', fullUser.role)

    const userCartKey = getCartKey(fullUser)
    cartKeyRef.current = userCartKey
    setCart(parseCartFromStorage(userCartKey))

    setUser(fullUser)
    toast.success(`Chào mừng trở lại, ${fullUser.name}!`, {
      description:
        fullUser.role === 'admin'
          ? 'Đã đăng nhập với quyền Quản Trị Viên.'
          : 'Bạn đã đăng nhập thành công vào Striker.',
    })
  }

  const logout = () => {
    localStorage.removeItem('crs_token')
    localStorage.removeItem('crs_user')
    localStorage.removeItem('crs_role')
    localStorage.removeItem('crs_coupon')
    cartKeyRef.current = 'cart_guest'
    setCart([])
    setAppliedCoupon(null)
    setOrders([])
    setUser(null)
    toast.info('Đã đăng xuất tài khoản')
  }

  const updateUserProfile = (data: Partial<User>) => {
    setUser((prev) => {
      if (!prev) return null
      const updated = { ...prev, ...data }
      localStorage.setItem('crs_user', JSON.stringify(updated))
      return updated
    })
    toast.success('Đã lưu thay đổi thông tin cá nhân!')
  }

  const addAddress = async (newAddr: Omit<Address, 'id'>): Promise<Address | null> => {
    if (!user?.id) return null
    try {
      const created = await apiCreateAddress(user.id as number, newAddr)
      setUser((prev) => {
        if (!prev) return null
        const addresses = (prev.addresses ?? [])
        const updated = created.isDefault
          ? addresses.map((a) => ({ ...a, isDefault: false }))
          : [...addresses]
        const nextUser = { ...prev, addresses: [...updated, created] }
        localStorage.setItem('crs_user', JSON.stringify(nextUser))
        return nextUser
      })
      toast.success('Đã thêm địa chỉ nhận hàng mới!')
      return created
    } catch (e) {
      toast.error('Không thể thêm địa chỉ. Vui lòng thử lại.')
      throw e
    }
  }

  const updateAddress = async (id: string, addrData: Partial<Address>): Promise<Address | null> => {
    if (!user?.id) return null
    try {
      const updated = await apiUpdateAddress(id, addrData)
      setUser((prev) => {
        if (!prev) return null
        const addresses = (prev.addresses ?? [])
        const nextAddresses = addresses.map((a) => {
          if (String(a.id) === String(id)) {
            return { ...a, ...updated }
          }
          if (updated.isDefault) {
            return { ...a, isDefault: false }
          }
          return a
        })
        const nextUser = { ...prev, addresses: nextAddresses }
        localStorage.setItem('crs_user', JSON.stringify(nextUser))
        return nextUser
      })
      toast.success('Đã cập nhật địa chỉ thành công!')
      return updated
    } catch (e) {
      toast.error('Không thể cập nhật địa chỉ. Vui lòng thử lại.')
      throw e
    }
  }

  const deleteAddress = async (id: string) => {
    if (!user?.id) return
    try {
      await apiDeleteAddress(id)
      setUser((prev) => {
        if (!prev) return null
        const addresses = (prev.addresses ?? []).filter((a) => a.id !== id)
        const nextUser = { ...prev, addresses }
        localStorage.setItem('crs_user', JSON.stringify(nextUser))
        return nextUser
      })
      toast.info('Đã xóa địa chỉ khỏi sổ địa chỉ')
    } catch {
      toast.error('Không thể xóa địa chỉ. Vui lòng thử lại.')
    }
  }

  const setDefaultAddress = async (id: string) => {
    if (!user?.id) return
    try {
      await apiSetDefaultAddress(id)
      setUser((prev) => {
        if (!prev) return null
        const addresses = (prev.addresses ?? []).map((a) => ({ ...a, isDefault: a.id === id }))
        const nextUser = { ...prev, addresses }
        localStorage.setItem('crs_user', JSON.stringify(nextUser))
        return nextUser
      })
      toast.success('Đã đặt làm địa chỉ mặc định!')
    } catch {
      toast.error('Không thể cập nhật địa chỉ mặc định.')
    }
  }

  const addToCart = async (
    product: Product,
    quantity = 1,
    options: { size?: string; color?: string } = {}
  ) => {
    if (!user) {
      toast.error('Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng!')
      window.location.href = '/login'
      return
    }

    const chosenSize = options.size ?? product.sizes?.[0] ?? 'FreeSize'
    const chosenColor = options.color ?? product.colors?.[0] ?? 'Mặc định'
    const cartItemId = `${product.id}_${chosenSize}_${chosenColor}`

    // Calculate max available stock for this specific variant
    let maxStock = typeof product.stock === 'number' ? product.stock : 999
    if (product.variants && product.variants.length > 0) {
      const matchingVariant = product.variants.find(
        (v) =>
          String(v.attributes?.size ?? '').trim() === String(chosenSize).trim() &&
          String(v.attributes?.color ?? '').trim() === String(chosenColor).trim()
      )
      maxStock = matchingVariant ? matchingVariant.stock : 0
    }

    if (maxStock <= 0) {
      toast.error(`Biến thể (Size ${chosenSize} · ${chosenColor}) hiện đã hết hàng trong kho!`)
      return
    }

    const existing = cart.find((item) => item.cartItemId === cartItemId)
    const existingQty = existing ? existing.quantity : 0
    if (existingQty + quantity > maxStock) {
      toast.warning(`Kho chỉ còn ${maxStock} sản phẩm cho phân loại (Size ${chosenSize} · ${chosenColor}).`, {
        description: `Bạn đã có ${existingQty} sản phẩm trong giỏ hàng.`,
      })
      return
    }

    const chosenImage =
      product.image ||
      (product as any).image_url ||
      (Array.isArray(product.images) && product.images.length > 0 ? product.images[0] : '') ||
      ''

    setCart((current) => {
      const existingInCurrent = current.find((item) => item.cartItemId === cartItemId)
      if (existingInCurrent) {
        return current.map((item) =>
          item.cartItemId === cartItemId
            ? {
                ...item,
                image: item.image || chosenImage,
                quantity: item.quantity + quantity,
                selected: true,
              }
            : item
        )
      }
      return [
        ...current,
        {
          ...product,
          image: chosenImage,
          cartItemId,
          quantity,
          selectedSize: chosenSize,
          selectedColor: chosenColor,
          selected: true,
        },
      ]
    })

    setCartPulse(true)
    window.setTimeout(() => setCartPulse(false), 650)

    toast.success(`Đã thêm ${quantity}x "${product.name}" vào giỏ`, {
      description: `Phân loại: Size ${chosenSize} · ${chosenColor}`,
      action: {
        label: 'Xem giỏ',
        onClick: () => setCartDrawerOpen(true),
      },
    })

    if (user?.id) {
      try {
        await addCartItem({
          user_id: user.id as number,
          product_id: product.id,
          quantity,
          price: product.price,
        })
      } catch {
        // Fallback offline sync
      }
    }
  }

  const updateCart = (cartItemId: string, quantity: number) => {
    if (quantity <= 0) { removeFromCart(cartItemId); return }

    const targetItem = cart.find((i) => i.cartItemId === cartItemId)
    if (targetItem) {
      let maxStock = typeof targetItem.stock === 'number' ? targetItem.stock : 999
      if (targetItem.variants && targetItem.variants.length > 0) {
        const v = targetItem.variants.find(
          (variant) =>
            String(variant.attributes?.size ?? '').trim() === String(targetItem.selectedSize ?? '').trim() &&
            String(variant.attributes?.color ?? '').trim() === String(targetItem.selectedColor ?? '').trim()
        )
        if (v) maxStock = v.stock
      }

      if (quantity > maxStock) {
        toast.warning(`Kho chỉ còn tối đa ${maxStock} sản phẩm cho phân loại (Size ${targetItem.selectedSize} · ${targetItem.selectedColor}).`)
        setCart((current) =>
          current.map((i) => i.cartItemId === cartItemId ? { ...i, quantity: Math.max(1, maxStock) } : i)
        )
        return
      }
    }
    setCart((current) =>
      current.map((item) => item.cartItemId === cartItemId ? { ...item, quantity } : item)
    )
  }

  const removeFromCart = (cartItemId: string) => {
    const item = cart.find((i) => i.cartItemId === cartItemId)
    setCart((current) => current.filter((i) => i.cartItemId !== cartItemId))
    if (item) { toast.info(`Đã xóa "${item.name}" khỏi giỏ hàng`) }
  }

  const toggleCartItem = (cartItemId: string) => {
    setCart((current) =>
      current.map((item) =>
        item.cartItemId === cartItemId ? { ...item, selected: !item.selected } : item
      )
    )
  }

  const toggleSelectAll = () => {
    const allSelected = cart.every((item) => item.selected !== false)
    setCart((current) => current.map((item) => ({ ...item, selected: !allSelected })))
  }

  const updateCartVariant = (cartItemId: string, size: string, color: string) => {
    setCart((current) => {
      const source = current.find((item) => item.cartItemId === cartItemId)
      if (!source) return current

      let variantStock = typeof source.stock === 'number' ? source.stock : 999
      if (source.variants && source.variants.length > 0) {
        const matching = source.variants.find(
          (v) =>
            String(v.attributes?.size ?? '').trim() === String(size).trim() &&
            String(v.attributes?.color ?? '').trim() === String(color).trim()
        )
        variantStock = matching ? matching.stock : 0
      }

      if (variantStock <= 0) {
        toast.error(`Biến thể (Size ${size} · ${color}) hiện đã hết hàng trong kho!`)
        return current
      }

      const nextId = `${source.id}_${size}_${color}`
      const duplicate = current.find((item) => item.cartItemId === nextId && item.cartItemId !== cartItemId)
      if (duplicate) {
        const newQty = Math.min(variantStock, duplicate.quantity + source.quantity)
        return current
          .filter((item) => item.cartItemId !== cartItemId)
          .map((item) =>
            item.cartItemId === nextId
              ? { ...item, quantity: newQty, selectedSize: size, selectedColor: color }
              : item
          )
      }
      const adjustedQty = Math.min(variantStock, source.quantity)
      return current.map((item) =>
        item.cartItemId === cartItemId
          ? { ...item, cartItemId: nextId, selectedSize: size, selectedColor: color, quantity: adjustedQty }
          : item
      )
    })
    toast.success('Đã cập nhật phân loại sản phẩm')
  }

  const removePurchasedItems = (items: Array<{ id: number; selectedSize?: string; selectedColor?: string; cartItemId?: string }>) => {
    setCart((current) => {
      const remaining = current.filter((cartItem) => {
        const isPurchased = items.some((item) => {
          if (item.cartItemId && item.cartItemId === cartItem.cartItemId) return true
          const sameId = Number(item.id) === Number(cartItem.id)
          const sameSize = !item.selectedSize || String(item.selectedSize).trim() === String(cartItem.selectedSize).trim()
          const sameColor = !item.selectedColor || String(item.selectedColor).trim() === String(cartItem.selectedColor).trim()
          return sameId && sameSize && sameColor
        })
        return !isPurchased
      })
      try {
        localStorage.setItem(cartKeyRef.current, JSON.stringify(remaining))
      } catch { /* ignore */ }
      return remaining
    })
    setAppliedCoupon(null)
    try {
      localStorage.removeItem('crs_coupon')
    } catch { /* ignore */ }
  }

  const clearCart = () => {
    setCart([])
    setAppliedCoupon(null)
    try {
      localStorage.setItem(cartKeyRef.current, JSON.stringify([]))
      localStorage.removeItem('crs_coupon')
    } catch { /* ignore */ }
  }

  const selectedCartItems = cart.filter((item) => item.selected !== false)
  const cartSubtotal = selectedCartItems.reduce((sum, item) => sum + item.price * item.quantity, 0)
  const cartCount = cart.reduce((sum, item) => sum + item.quantity, 0)

  const totals = calculateOrderTotals(cartSubtotal, appliedCoupon)
  const shippingFee = totals.shippingFee
  const discountAmount = totals.discountAmount
  const cartTotal = totals.finalTotal

  const applyCoupon = async (code: string): Promise<{ success: boolean; message: string }> => {
    const normalizedCode = code.trim().toUpperCase()
    if (!normalizedCode) {
      return { success: false, message: 'Vui lòng nhập mã giảm giá.' }
    }

    try {
      const result = await apiApplyCoupon(normalizedCode, cartSubtotal, BASE_SHIPPING_FEE)
      const coupon = result?.coupon ?? result
      const frontendCoupon = mapBackendCoupon(coupon)
      setAppliedCoupon(frontendCoupon)

      const discountCalc = calculateOrderTotals(cartSubtotal, frontendCoupon)
      toast.success(`Áp dụng mã ${frontendCoupon.code} thành công! 🎉`, {
        description:
          frontendCoupon.discountType === 'freeship'
            ? 'Bạn được miễn phí phí giao hàng.'
            : `Đã giảm ${discountCalc.discountAmount.toLocaleString('vi-VN')}đ cho đơn hàng.`,
      })
      return { success: true, message: 'Áp dụng thành công' }
    } catch (err: any) {
      const msg: string =
        err?.response?.data?.message ??
        err?.response?.data?.errors?.code?.[0] ??
        'Mã giảm giá không hợp lệ!'
      toast.error(msg)
      return { success: false, message: msg }
    }
  }

  const removeCoupon = () => {
    if (appliedCoupon) {
      const code = appliedCoupon.code
      setAppliedCoupon(null)
      toast.info(`Đã gỡ mã giảm giá ${code}`)
    }
  }

  const saveCoupon = (code: string) => {
    const upper = code.toUpperCase()
    if (!savedCoupons.includes(upper)) {
      setSavedCoupons((prev) => [...prev, upper])
      toast.success(`Đã lưu mã ${upper} vào ví Voucher!`, {
        description: 'Bạn có thể chọn nhanh voucher này khi thanh toán.',
      })
    } else {
      toast.info(`Mã ${upper} đã có sẵn trong ví của bạn!`)
    }
  }

  const isCouponSaved = (code: string) => savedCoupons.includes(code.toUpperCase())

  const addOrder = (newOrder: Order) => {
    setOrders((prev) => [newOrder, ...prev])
  }

  const updateOrderStatus = (orderId: string, status: OrderStatus, paymentStatus?: PaymentStatus) => {
    setOrders((prev) =>
      prev.map((o) =>
        o.id === orderId
          ? { ...o, status, ...(paymentStatus ? { paymentStatus } : {}) }
          : o
      )
    )
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new Event('order-status-changed'))
    }
  }

  const cancelOrder = async (orderId: string) => {
    try {
      const order = orders.find((o) => o.id === orderId)
      const dbId = order ? orderId : orderId
      await apiCancelOrder(dbId)
      updateOrderStatus(orderId, 'cancelled')
      toast.success(`Đã hủy đơn hàng ${orderId} thành công!`)
    } catch {
      toast.error('Không thể hủy đơn hàng. Vui lòng thử lại.')
    }
  }

  const value: AppContextValue = {
    user,
    cart,
    isAuthenticated: Boolean(user),
    authModalOpen: false,
    setAuthModalOpen,
    login,
    logout,
    updateUserProfile,
    addAddress,
    updateAddress,
    deleteAddress,
    setDefaultAddress,
    addToCart,
    updateCart,
    removeFromCart,
    toggleCartItem,
    toggleSelectAll,
    updateCartVariant,
    removePurchasedItems,
    clearCart,
    cartCount,
    cartSubtotal,
    cartTotal,
    shippingFee,
    discountAmount,
    appliedCoupon,
    applyCoupon,
    removeCoupon,
    savedCoupons,
    saveCoupon,
    isCouponSaved,
    orders,
    ordersLoading,
    refreshOrders,
    addOrder,
    updateOrderStatus,
    cancelOrder,
    toast: legacyToast,
    notify,
    cartDrawerOpen,
    setCartDrawerOpen,
    cartPulse,
  }

  return <AppContext.Provider value={value}>{children}</AppContext.Provider>
}

export function useApp() {
  const context = useContext(AppContext)
  if (!context) throw new Error('useApp must be used inside AppProvider')
  return context
}

export function roleLabel(role: Role) {
  return role === 'admin' ? 'Quản Trị Viên' : 'Khách hàng'
}