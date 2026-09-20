import React, { useState, useMemo, useEffect, useCallback } from 'react';
import { 
  ShoppingBag, 
  Search, 
  Eye, 
  Copy, 
  Truck, 
  Clock, 
  CheckCircle2, 
  Download, 
  X, 
  MapPin, 
  Phone, 
  Ban, 
  ShieldCheck,
  Zap,
  Package,
  RefreshCw
} from 'lucide-react';
import { toast } from 'sonner';
import { useApp } from '../../context/AppContext';
import { fetchAdminOrders, mapBackendOrder } from '../../services/orders';
import api from '../../services/api';
import type { Order, OrderStatus, PaymentStatus } from '../../types';

export const Orders: React.FC = () => {
  const { updateOrderStatus } = useApp();

  const [localOrders, setLocalOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [isRefreshing, setIsRefreshing] = useState<boolean>(false);

  const fetchOrdersFromApi = useCallback(async (showToast = false) => {
    try {
      setIsRefreshing(true);
      const raw = await fetchAdminOrders({ per_page: 50 });
      const list: any[] = Array.isArray(raw) ? raw : (raw?.data ?? []);
      const mapped = list.map(mapBackendOrder);
      setLocalOrders(mapped);
      if (typeof window !== 'undefined') {
        window.dispatchEvent(new Event('order-status-changed'));
      }
      if (showToast) {
        toast.success('Đã làm mới danh sách đơn hàng!');
      }
    } catch (error) {
      console.error('Lỗi khi tải đơn hàng Admin:', error);
      toast.error('Không thể tải danh sách đơn hàng từ máy chủ.');
    } finally {
      setLoading(false);
      setIsRefreshing(false);
    }
  }, []);

  // Fetch fresh orders on component mount (staleTime = 0)
  useEffect(() => {
    void fetchOrdersFromApi(false);
  }, [fetchOrdersFromApi]);

  const [statusFilter, setStatusFilter] = useState<string>('ALL');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);

  // Helper: Determine combined payment method & collection status badge
  const getPaymentBadge = (order: Order): { isPaid: boolean; label: string; className: string } => {
    const isMoMo = order.paymentMethod === 'momo';
    
    // Delivered COD or Online gateway or explicitly marked paid
    const isPaid = 
      order.paymentStatus === 'paid' || 
      order.status === 'delivered' || 
      order.status === 'paid';

    if (isMoMo) {
      return {
        isPaid,
        label: isPaid ? 'MoMo • Đã thanh toán' : 'MoMo • Chờ thanh toán',
        className: isPaid 
          ? 'bg-pink-500/15 text-pink-400 border-pink-500/30 font-bold' 
          : 'bg-pink-950/40 text-pink-300 border-pink-800/40'
      };
    }

    return {
      isPaid,
      label: isPaid ? 'COD • Đã thu tiền' : 'COD • Chưa thu',
      className: isPaid 
        ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 font-bold' 
        : 'bg-zinc-800 text-amber-400 border-zinc-700'
    };
  };

  // Filtered Orders (Filtered strictly by 5 shipping status keys: ALL, pending, shipping, delivered, cancelled)
  const filteredOrders = useMemo(() => {
    return localOrders.filter((order) => {
      const matchesStatus =
        statusFilter === 'ALL' || 
        order.status === statusFilter;

      const q = searchQuery.toLowerCase().trim();
      const matchesSearch =
        !q ||
        order.id.toLowerCase().includes(q) ||
        order.customer.name.toLowerCase().includes(q) ||
        order.customer.phone.includes(q) ||
        (order.customer.email && order.customer.email.toLowerCase().includes(q)) ||
        (order.ghn_code && order.ghn_code.toLowerCase().includes(q)) ||
        (order.ghnTrackingCode && order.ghnTrackingCode.toLowerCase().includes(q)) ||
        (order.appliedCouponCode && order.appliedCouponCode.toLowerCase().includes(q));

      return matchesStatus && matchesSearch;
    });
  }, [localOrders, statusFilter, searchQuery]);

  // Statistics KPI - Doanh thu chỉ tính đơn đã giao / đã thanh toán thành công
  const totalRevenue = useMemo(
    () => localOrders.filter((o) => (o.status === 'delivered' || o.status === 'paid' || o.paymentStatus === 'paid') && o.status !== 'cancelled').reduce((sum, o) => sum + o.total, 0),
    [localOrders]
  );
  const pendingCount = useMemo(() => localOrders.filter((o) => o.status === 'pending').length, [localOrders]);
  const shippingCount = useMemo(() => localOrders.filter((o) => o.status === 'shipping').length, [localOrders]);
  const deliveredCount = useMemo(() => localOrders.filter((o) => o.status === 'delivered' || o.status === 'paid').length, [localOrders]);
  const cancelledCount = useMemo(() => localOrders.filter((o) => o.status === 'cancelled').length, [localOrders]);

  // Copy helper
  const handleCopy = (text: string, label: string) => {
    navigator.clipboard.writeText(text);
    toast.success(`Đã sao chép ${label}: ${text}`);
  };

  // GHN API Action: Push order to Giao Hàng Nhanh
  const handleCreateGHNOrder = async (order: Order) => {
    try {
      const response = await api.post(`/orders/${order.id}/ship-ghn`);
      const ghnCode = response.data?.ghn_code ?? response.data?.data?.ghn_code ?? response.data?.data?.order_code;

      if (!ghnCode) {
        throw new Error(response.data?.message ?? 'Không nhận được mã vận đơn từ GHN API.');
      }

      const updatedOrder: Order = {
        ...order,
        ghn_code: ghnCode,
        ghnTrackingCode: ghnCode,
        status: 'shipping',
      };

      setLocalOrders((prev) =>
        prev.map((o) => (o.id === order.id ? updatedOrder : o))
      );
      updateOrderStatus(order.id, 'shipping');

      if (selectedOrder && selectedOrder.id === order.id) {
        setSelectedOrder(updatedOrder);
      }

      toast.success(`⚡ Đã tạo đơn Giao Hàng Nhanh thành công!`, {
        description: `Mã vận đơn GHN: ${ghnCode} • Trạng thái: Đang giao hàng`,
      });
    } catch (e: any) {
      const errorMsg =
        e?.response?.data?.message ??
        e?.response?.data?.error?.message ??
        (Array.isArray(e?.response?.data?.errors?.ghn) ? e.response.data.errors.ghn[0] : null) ??
        (Array.isArray(e?.response?.data?.error?.details?.ghn) ? e.response.data.error.details.ghn[0] : null) ??
        (typeof e?.response?.data?.error === 'string' ? e.response.data.error : null) ??
        e?.message ??
        'Tạo đơn GHN thất bại.';
      toast.error(`❌ ${errorMsg}`);
    }
  };

  // Cancel order before pushing to GHN
  const handleCancelOrder = async (orderId: string) => {
    if (!window.confirm(`Bạn có chắc chắn muốn hủy đơn hàng #${orderId}?`)) {
      return;
    }

    try {
      await api.patch(`/orders/${orderId}/status`, { order_status: 'cancelled' });
    } catch {
      // ignore
    }

    const updatedStatus: OrderStatus = 'cancelled';
    setLocalOrders((prev) =>
      prev.map((o) => (o.id === orderId ? { ...o, status: updatedStatus } : o))
    );
    updateOrderStatus(orderId, updatedStatus);

    if (selectedOrder && selectedOrder.id === orderId) {
      setSelectedOrder((prev) => (prev ? { ...prev, status: updatedStatus } : null));
    }

    toast.info(`Đã hủy đơn hàng #${orderId}.`);
  };

  // Simulate GHN Webhook: Delivered webhook
  const handleSimulateGHNDelivered = async (orderId: string) => {
    const updatedStatus: OrderStatus = 'delivered';
    const updatedPayment: PaymentStatus = 'paid';

    try {
      await api.patch(`/orders/${orderId}/status`, { order_status: 'delivered', payment_status: 'paid' });
    } catch {
      // ignore
    }

    setLocalOrders((prev) =>
      prev.map((o) =>
        o.id === orderId
          ? {
              ...o,
              status: updatedStatus,
              paymentStatus: updatedPayment,
            }
          : o
      )
    );
    updateOrderStatus(orderId, updatedStatus, updatedPayment);

    if (selectedOrder && selectedOrder.id === orderId) {
      setSelectedOrder((prev) =>
        prev
          ? {
              ...prev,
              status: updatedStatus,
              paymentStatus: updatedPayment,
            }
          : null
      );
    }

    toast.success(`🔔 GHN: Đơn hàng #${orderId} đã giao thành công!`, {
      description: 'Hệ thống đã tự động cập nhật trạng thái thanh toán sang [Đã thu tiền].',
    });
  };

  // Export to CSV
  const handleExportCSV = () => {
    const csvHeaders = 'Mã đơn,Ngày,Khách hàng,SĐT,Tổng tiền,Phương thức,Trạng thái GHN,Thanh toán,Mã vận đơn GHN\n';
    const csvRows = filteredOrders.map((o) => {
      const p = getPaymentBadge(o);
      return `"${o.id}","${o.date}","${o.customer.name}","${o.customer.phone}",${o.total},"${o.paymentMethod}","${o.status}","${p.label}","${o.ghnTrackingCode || ''}"`;
    }).join('\n');
    const blob = new Blob([csvHeaders + csvRows], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `striker-orders-${Date.now()}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    toast.success('Đã xuất file CSV danh sách đơn hàng!');
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      {/* 1. Header Metrics */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-zinc-900/60 backdrop-blur-xl border border-zinc-800/80 p-6 rounded-3xl shadow-xl">
        <div>
          <div className="flex items-center gap-2 text-xs font-mono text-lime-400 font-semibold uppercase tracking-widest">
            <ShoppingBag className="w-4 h-4" />
            <span>QUẢN LÝ ĐƠN HÀNG</span>
          </div>
          <h1 className="text-2xl font-black text-white mt-1 uppercase tracking-tight">
            Quản Lý Đơn Hàng
          </h1>
          <p className="text-xs text-zinc-400 mt-1 font-mono">
            Doanh thu đã giao: <b className="text-lime-400">{totalRevenue.toLocaleString('vi-VN')}₫</b> •{' '}
            <b className="text-white">{localOrders.length}</b> đơn hàng.
          </p>
        </div>

        <div className="flex items-center gap-2.5">
          <button
            onClick={() => void fetchOrdersFromApi(true)}
            disabled={isRefreshing}
            className="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-xs font-bold text-zinc-200 transition hover:border-lime-400/50 shadow-lg disabled:opacity-50 cursor-pointer"
            title="Tải lại danh sách mới nhất từ máy chủ"
          >
            <RefreshCw className={`w-4 h-4 text-lime-400 ${isRefreshing ? 'animate-spin' : ''}`} />
            <span>{isRefreshing ? 'Đang tải...' : 'Làm mới'}</span>
          </button>

          <button
            onClick={handleExportCSV}
            className="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-xs font-bold text-white transition hover:border-lime-400/50 shadow-lg cursor-pointer"
          >
            <Download className="w-4 h-4 text-lime-400" />
            <span>Xuất CSV</span>
          </button>
        </div>
      </div>

      {/* 2. Quick Status Counters (5-Column Interactive Grid) */}
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        {/* 1. Tất cả đơn */}
        <button
          onClick={() => setStatusFilter('ALL')}
          className={`p-4 rounded-2xl border transition-all text-left cursor-pointer ${
            statusFilter === 'ALL'
              ? 'bg-lime-400/10 border-lime-400/60 shadow-lg shadow-lime-400/10 scale-[1.02]'
              : 'bg-zinc-900/60 border-zinc-800/80 hover:bg-zinc-800/60 hover:border-zinc-700'
          }`}
        >
          <span className="text-xs text-zinc-400 font-medium">Tất cả đơn</span>
          <div className="text-xl font-mono font-black text-white mt-1">{localOrders.length}</div>
        </button>

        {/* 2. ⏳ Chờ xử lý */}
        <button
          onClick={() => setStatusFilter('pending')}
          className={`p-4 rounded-2xl border transition-all text-left cursor-pointer ${
            statusFilter === 'pending'
              ? 'bg-amber-400/10 border-amber-400/60 shadow-lg shadow-amber-400/10 scale-[1.02]'
              : 'bg-zinc-900/60 border-zinc-800/80 hover:bg-zinc-800/60 hover:border-amber-500/30'
          }`}
        >
          <span className="text-xs text-amber-400 font-medium flex items-center gap-1">
            <Clock className="w-3.5 h-3.5" /> Chờ xử lý
          </span>
          <div className="text-xl font-mono font-black text-amber-300 mt-1">{pendingCount}</div>
        </button>

        {/* 3. 🚚 Đang giao */}
        <button
          onClick={() => setStatusFilter('shipping')}
          className={`p-4 rounded-2xl border transition-all text-left cursor-pointer ${
            statusFilter === 'shipping'
              ? 'bg-sky-400/10 border-sky-400/60 shadow-lg shadow-sky-400/10 scale-[1.02]'
              : 'bg-zinc-900/60 border-zinc-800/80 hover:bg-zinc-800/60 hover:border-sky-500/30'
          }`}
        >
          <span className="text-xs text-sky-400 font-medium flex items-center gap-1">
            <Truck className="w-3.5 h-3.5" /> Đang giao
          </span>
          <div className="text-xl font-mono font-black text-sky-300 mt-1">{shippingCount}</div>
        </button>

        {/* 4. ✅ Đã giao */}
        <button
          onClick={() => setStatusFilter('delivered')}
          className={`p-4 rounded-2xl border transition-all text-left cursor-pointer ${
            statusFilter === 'delivered'
              ? 'bg-emerald-400/10 border-emerald-400/60 shadow-lg shadow-emerald-400/10 scale-[1.02]'
              : 'bg-zinc-900/60 border-zinc-800/80 hover:bg-zinc-800/60 hover:border-emerald-500/30'
          }`}
        >
          <span className="text-xs text-emerald-400 font-medium flex items-center gap-1">
            <CheckCircle2 className="w-3.5 h-3.5" /> Đã giao
          </span>
          <div className="text-xl font-mono font-black text-emerald-300 mt-1">{deliveredCount}</div>
        </button>

        {/* 5. ❌ Đã hủy */}
        <button
          onClick={() => setStatusFilter('cancelled')}
          className={`p-4 rounded-2xl border transition-all text-left col-span-2 sm:col-span-1 cursor-pointer ${
            statusFilter === 'cancelled'
              ? 'bg-rose-500/15 border-rose-500/60 shadow-lg shadow-rose-500/10 scale-[1.02]'
              : 'bg-zinc-900/60 border-rose-500/20 hover:bg-zinc-800/60 hover:border-rose-500/40'
          }`}
        >
          <span className="text-xs text-rose-400 font-medium flex items-center gap-1">
            <Ban className="w-3.5 h-3.5" /> Đã hủy
          </span>
          <div className="text-xl font-mono font-black text-rose-400 mt-1">{cancelledCount}</div>
        </button>
      </div>

      {/* 3. Search & Standard 5 Shipping Filter Tabs */}
      <div className="bg-zinc-900/60 backdrop-blur-xl border border-zinc-800/80 p-4 rounded-2xl shadow-xl flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
        <div className="relative flex-1">
          <Search className="w-4 h-4 text-zinc-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Tìm theo mã đơn #STR, tên khách, SĐT, mã GHN, voucher..."
            className="w-full bg-zinc-950/80 border border-zinc-800 text-sm text-zinc-200 placeholder-zinc-500 pl-10 pr-4 py-2.5 rounded-xl focus:outline-none focus:border-lime-400/60 transition"
          />
        </div>

        {/* 5 Standard Shipping Filter Tabs */}
        <div className="flex flex-wrap gap-1.5">
          {['ALL', 'pending', 'shipping', 'delivered', 'cancelled'].map((key) => (
            <button
              key={key}
              onClick={() => setStatusFilter(key)}
              className={`px-3 py-1.5 rounded-xl text-xs font-bold transition ${
                statusFilter === key
                  ? 'bg-lime-400 text-zinc-950 shadow-md shadow-lime-400/20'
                  : 'bg-zinc-950 border border-zinc-800 text-zinc-400 hover:text-white'
              }`}
            >
              {key === 'ALL'
                ? 'Tất cả'
                : key === 'pending'
                ? 'Chờ xử lý'
                : key === 'shipping'
                ? 'Đang giao'
                : key === 'delivered'
                ? 'Đã giao'
                : 'Đã hủy'}
            </button>
          ))}
        </div>
      </div>

      {/* 4. Orders Table - 5 Clean Columns */}
      <div className="bg-zinc-900/60 backdrop-blur-xl border border-zinc-800/80 rounded-3xl shadow-2xl overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse min-w-[900px]">
            <thead>
              <tr className="border-b border-zinc-800 bg-zinc-950/40 text-[11px] font-mono uppercase tracking-wider text-zinc-400">
                <th className="py-4 px-6">MÃ ĐƠN</th>
                <th className="py-4 px-4">KHÁCH HÀNG</th>
                <th className="py-4 px-4">TỔNG TIỀN</th>
                <th className="py-4 px-4">TRẠNG THÁI</th>
                <th className="py-4 px-6 text-right">THAO TÁC</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-zinc-800/60 text-sm">
              {filteredOrders.map((order) => {
                const paymentBadge = getPaymentBadge(order);
                const ghnCode = order.ghn_code || order.ghnTrackingCode;
                const hasGHN = Boolean(ghnCode);
                const isPending = order.status === 'pending' && !hasGHN;
                const isShipping = order.status === 'shipping';
                const isDelivered = order.status === 'delivered';
                const isCancelled = order.status === 'cancelled';

                return (
                  <tr
                    key={order.id}
                    className={`hover:bg-zinc-800/40 transition-colors group ${
                      isCancelled ? 'opacity-60 bg-zinc-950/30' : ''
                    }`}
                  >
                    {/* Cột 1: Mã đơn & Ngày đặt nhỏ mờ bên dưới */}
                    <td className="py-4 px-6">
                      <div className="flex items-center gap-2">
                        <span 
                          className="font-mono font-bold text-lime-400 group-hover:underline cursor-pointer" 
                          onClick={() => setSelectedOrder(order)}
                        >
                          {order.id}
                        </span>
                        <button
                          onClick={() => handleCopy(order.id, 'Mã đơn')}
                          className="text-zinc-500 hover:text-white transition"
                          title="Sao chép mã đơn"
                        >
                          <Copy className="w-3.5 h-3.5" />
                        </button>
                      </div>
                      <div className="text-xs text-neutral-400 font-mono mt-0.5">
                        {order.date}
                      </div>
                    </td>

                    {/* Cột 2: Khách hàng (Tên, SĐT, Tỉnh/Thành) */}
                    <td className="py-4 px-4">
                      <div className="font-bold text-white">{order.customer.name}</div>
                      <div className="text-xs text-zinc-400 font-mono mt-0.5">{order.customer.phone}</div>
                      <div className="text-[11px] text-zinc-400 truncate max-w-[160px]" title={order.customer.address}>
                        {order.customer.city}
                      </div>
                    </td>

                    {/* Cột 3: Tổng tiền (Dòng 1: Số tiền, Dòng 2: Badge PTTT & Trạng thái thu tiền) */}
                    <td className="py-4 px-4">
                      <div className="font-mono font-black text-white text-[15px]">
                        {order.total.toLocaleString('vi-VN')}₫
                      </div>
                      <div className="flex items-center gap-1.5 mt-1">
                        <span
                          className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-mono font-bold border ${paymentBadge.className}`}
                        >
                          {paymentBadge.isPaid ? <ShieldCheck className="w-3 h-3" /> : <Clock className="w-3 h-3" />}
                          <span>{paymentBadge.label}</span>
                        </span>
                        {order.appliedCouponCode && (
                          <span className="text-[10px] font-mono font-bold text-lime-400 bg-lime-400/10 px-1 py-0.5 rounded">
                            {order.appliedCouponCode}
                          </span>
                        )}
                      </div>
                    </td>

                    {/* Cột 4: Trạng thái (Badge tĩnh) */}
                    <td className="py-4 px-4">
                      {isDelivered ? (
                        <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-mono text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                          <CheckCircle2 className="w-3.5 h-3.5" /> Đã giao
                        </span>
                      ) : isShipping ? (
                        <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-mono text-xs font-bold bg-sky-500/10 text-sky-400 border border-sky-500/30">
                          <Truck className="w-3.5 h-3.5" /> Đang giao
                        </span>
                      ) : isCancelled ? (
                        <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-mono text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/30">
                          <Ban className="w-3.5 h-3.5" /> Đã hủy
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-mono text-xs font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">
                          <Clock className="w-3.5 h-3.5" /> Chờ xử lý
                        </span>
                      )}
                    </td>

                    {/* Cột 5: Thao tác (Tạo đơn GHN / Badge Mã GHN thật / Chi tiết) */}
                    <td className="py-4 px-6 text-right">
                      <div className="flex items-center justify-end gap-2">
                        {/* TH 1: Chưa tạo đơn GHN & Đơn ở trạng thái Chờ xử lý */}
                        {isPending && (
                          <button
                            onClick={() => handleCreateGHNOrder(order)}
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-lime-400 hover:bg-lime-300 text-zinc-950 font-black text-xs uppercase tracking-wider shadow-md shadow-lime-400/20 hover:scale-105 transition"
                            title="Tạo đơn Giao Hàng Nhanh (GHN)"
                          >
                            <Package className="w-3.5 h-3.5 stroke-[2.5]" />
                            <span>Tạo đơn GHN</span>
                          </button>
                        )}

                        {/* TH 2: Đã có mã GHN thật -> Badge click để copy */}
                        {hasGHN && (
                          <button
                            onClick={() => handleCopy(ghnCode!, 'Mã vận đơn GHN')}
                            className="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-zinc-950 border border-sky-500/30 hover:border-sky-400 text-xs font-mono text-sky-400 font-bold shadow-sm transition hover:bg-sky-500/10 cursor-pointer"
                            title="Bấm để sao chép mã GHN"
                          >
                            <Truck className="w-3.5 h-3.5 text-sky-400" />
                            <span>{ghnCode}</span>
                            <Copy className="w-3 h-3 text-zinc-400" />
                          </button>
                        )}

                        {/* TH 3 & Mặc định: Nút Xem chi tiết */}
                        <button
                          onClick={() => setSelectedOrder(order)}
                          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-800 hover:bg-lime-400 hover:text-zinc-950 text-zinc-300 text-xs font-bold transition group"
                          title="Xem chi tiết đơn hàng"
                        >
                          <Eye className="w-3.5 h-3.5" />
                          <span>Chi tiết</span>
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })}

              {loading ? (
                <tr>
                  <td colSpan={5} className="py-16 text-center text-zinc-400">
                    <RefreshCw className="w-8 h-8 mx-auto mb-3 text-lime-400 animate-spin" />
                    <p className="text-sm font-semibold">Đang tải danh sách đơn hàng...</p>
                  </td>
                </tr>
              ) : filteredOrders.length === 0 ? (
                <tr>
                  <td colSpan={5} className="py-12 text-center text-zinc-500">
                    <ShoppingBag className="w-12 h-12 mx-auto mb-3 opacity-30 text-lime-400" />
                    <p className="text-sm font-semibold">Không tìm thấy đơn hàng nào phù hợp.</p>
                  </td>
                </tr>
              ) : null}
            </tbody>
          </table>
        </div>
      </div>

      {/* 5. Order Detail Modal with Standard 4-Step GHN Timeline */}
      {selectedOrder && (() => {
        const paymentBadge = getPaymentBadge(selectedOrder);
        const ghnCode = selectedOrder.ghn_code || selectedOrder.ghnTrackingCode;
        const hasGHN = Boolean(ghnCode);
        const isPending = selectedOrder.status === 'pending' && !hasGHN;
        const isShipping = selectedOrder.status === 'shipping';
        const isDelivered = selectedOrder.status === 'delivered';
        const isCancelled = selectedOrder.status === 'cancelled';
        const currentStepIndex = isDelivered ? 4 : isShipping ? 3 : hasGHN ? 2 : 1;

        // 4-Step GHN Timeline steps
        const stepperSteps = [
          { step: 1, title: 'Khách đặt' },
          { step: 2, title: 'Đã tạo GHN' },
          { step: 3, title: 'Đang giao' },
          { step: 4, title: 'Đã giao' },
        ];

        return (
          <div className="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-4 overflow-y-auto">
            <div className="bg-zinc-900 border border-zinc-800 w-full max-w-3xl rounded-3xl shadow-2xl p-6 sm:p-8 max-h-[92vh] overflow-y-auto custom-scrollbar animate-in zoom-in-95 duration-200">
              {/* Modal Header */}
              <div className="flex items-center justify-between pb-4 border-b border-zinc-800">
                <div>
                  <div className="flex items-center gap-2">
                    <span className="text-[11px] font-mono font-bold uppercase tracking-widest text-lime-400">
                      CHI TIẾT ĐƠN HÀNG
                    </span>
                    <span className="text-xs text-zinc-500">•</span>
                    <span className="text-xs font-mono text-zinc-400">{selectedOrder.date}</span>
                  </div>
                  <h2 className="text-2xl font-black font-mono text-white mt-0.5 flex items-center gap-2">
                    <span>{selectedOrder.id}</span>
                    {ghnCode && (
                      <span className="text-xs font-mono bg-sky-500/10 text-sky-400 border border-sky-500/30 px-2.5 py-1 rounded-xl">
                        {ghnCode}
                      </span>
                    )}
                  </h2>
                </div>
                <button
                  onClick={() => setSelectedOrder(null)}
                  className="p-2 rounded-xl text-zinc-400 hover:text-white hover:bg-zinc-800 transition"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              {/* Minimal Line Stepper Timeline */}
              {isCancelled ? (
                <div className="my-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center justify-between">
                  <div className="flex items-center gap-2.5 text-sm font-bold text-red-400">
                    <span className="text-base">🔴</span>
                    <span>Đơn hàng này đã bị hủy</span>
                  </div>
                  <span className="text-xs font-mono text-zinc-500">Không vận chuyển</span>
                </div>
              ) : (
                <div className="my-6 p-5 sm:p-6 rounded-2xl bg-zinc-950/80 border border-zinc-800">
                  <div className="flex items-center gap-2 text-xs font-mono font-bold uppercase text-zinc-400 mb-6">
                    <Truck className="w-4 h-4 text-lime-400" />
                    <span>TIẾN TRÌNH GIAO HÀNG</span>
                  </div>

                  <div className="relative px-2 sm:px-4">
                    {/* Background Connecting Line */}
                    <div className="absolute top-4 left-6 right-6 -translate-y-1/2 h-0.5 bg-zinc-800 z-0" />
                    {/* Active Progress Line */}
                    <div 
                      className="absolute top-4 left-6 -translate-y-1/2 h-0.5 bg-lime-400 z-0 transition-all duration-300"
                      style={{
                        width: currentStepIndex === 1 ? '0%' : currentStepIndex === 2 ? '33.33%' : currentStepIndex === 3 ? '66.66%' : 'calc(100% - 48px)'
                      }}
                    />

                    <div className="relative z-10 flex justify-between items-start">
                      {stepperSteps.map((s) => {
                        const isDone = currentStepIndex >= s.step;
                        return (
                          <div key={s.step} className="flex flex-col items-center">
                            <div
                              className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-mono font-black transition-all ring-4 ring-zinc-950 ${
                                isDone
                                  ? 'bg-lime-400 text-zinc-950 shadow-lg shadow-lime-400/30'
                                  : 'bg-zinc-800 text-zinc-500 border border-zinc-700'
                              }`}
                            >
                              {isDone ? '✓' : s.step}
                            </div>
                            <span
                              className={`text-xs mt-2 font-bold tracking-tight text-center ${
                                isDone ? 'text-white' : 'text-zinc-500'
                              }`}
                            >
                              {s.title}
                            </span>
                          </div>
                        );
                      })}
                    </div>
                  </div>
                </div>
              )}

              {/* Customer & Shipping Info */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div className="p-4 rounded-2xl bg-zinc-950 border border-zinc-800">
                  <h4 className="text-xs font-bold text-lime-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <Phone className="w-3.5 h-3.5" /> Khách hàng & Liên hệ
                  </h4>
                  <div className="text-sm font-bold text-white">{selectedOrder.customer.name}</div>
                  <div className="text-xs text-zinc-300 font-mono mt-1">SĐT: {selectedOrder.customer.phone}</div>
                  {selectedOrder.customer.email && (
                    <div className="text-xs text-zinc-400 mt-0.5">Email: {selectedOrder.customer.email}</div>
                  )}
                </div>

                <div className="p-4 rounded-2xl bg-zinc-950 border border-zinc-800">
                  <h4 className="text-xs font-bold text-sky-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <MapPin className="w-3.5 h-3.5" /> Địa chỉ giao hàng (GHN)
                  </h4>
                  <div className="text-xs text-zinc-200 leading-relaxed">
                    {selectedOrder.customer.address?.includes(',')
                      ? selectedOrder.customer.address
                      : [
                          selectedOrder.customer.address,
                          selectedOrder.customer.ward,
                          selectedOrder.customer.district,
                          selectedOrder.customer.city,
                        ].filter(Boolean).join(', ')}
                  </div>
                  {selectedOrder.customer.note && (
                    <div className="text-xs text-amber-400 mt-2 font-medium bg-amber-400/10 p-2 rounded-lg border border-amber-400/20">
                      Ghi chú: {selectedOrder.customer.note}
                    </div>
                  )}
                </div>
              </div>

              {/* Line Items Breakdown */}
              <div className="space-y-3 mb-6">
                <h4 className="text-xs font-bold text-zinc-400 uppercase tracking-wider">
                  Danh sách sản phẩm ({selectedOrder.itemsCount} mặt hàng)
                </h4>
                <div className="divide-y divide-zinc-800 border border-zinc-800 rounded-2xl overflow-hidden bg-zinc-950">
                  {selectedOrder.itemsList.map((item, i) => (
                    <div key={i} className="p-3.5 flex items-center justify-between gap-3">
                      <div className="flex items-center gap-3">
                        <img
                          src={
                            item.image?.startsWith('http') || item.image?.startsWith('data:')
                              ? item.image
                              : item.image?.startsWith('/storage/')
                              ? `http://localhost:8000${item.image}`
                              : `http://localhost:8000/storage/${item.image || ''}`
                          }
                          alt={item.name}
                          className="w-12 h-12 rounded-xl object-cover border border-zinc-800"
                          onError={(e) => {
                            (e.currentTarget as HTMLImageElement).src =
                              'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=300&q=80'
                          }}
                        />
                        <div>
                          <div className="font-bold text-sm text-white">{item.name}</div>
                          <div className="text-xs text-zinc-400 mt-0.5 font-mono">
                            {item.selectedSize && `Size: ${item.selectedSize}`} • {item.selectedColor && `Màu: ${item.selectedColor}`}
                          </div>
                        </div>
                      </div>

                      <div className="text-right font-mono">
                        <div className="text-sm font-bold text-lime-400">
                          {(item.price * item.quantity).toLocaleString('vi-VN')}₫
                        </div>
                        <div className="text-xs text-zinc-500">
                          {item.price.toLocaleString('vi-VN')}₫ x {item.quantity}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Price Calculations & Payment Status */}
              <div className="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 space-y-2.5 mb-6 text-sm">
                <div className="flex justify-between text-zinc-400">
                  <span>Tạm tính tiền hàng:</span>
                  <span className="font-mono text-white">{selectedOrder.subtotal.toLocaleString('vi-VN')}₫</span>
                </div>
                <div className="flex justify-between text-zinc-400">
                  <span>Phí vận chuyển:</span>
                  <span className="font-mono text-white">{selectedOrder.shippingFee.toLocaleString('vi-VN')}₫</span>
                </div>
                {selectedOrder.discountAmount > 0 && (
                  <div className="flex justify-between text-lime-400">
                    <span>Giảm giá Voucher ({selectedOrder.appliedCouponCode}):</span>
                    <span className="font-mono">-{selectedOrder.discountAmount.toLocaleString('vi-VN')}₫</span>
                  </div>
                )}
                
                <div className="pt-2 border-t border-zinc-800 flex justify-between items-center font-black text-lg text-white">
                  <span>Tổng thanh toán:</span>
                  <span className="font-mono text-lime-400">{selectedOrder.total.toLocaleString('vi-VN')}₫</span>
                </div>

                {/* Read-only Payment Status Badge in Modal */}
                <div className="pt-2 border-t border-zinc-800/80 flex items-center justify-between">
                  <span className="text-xs text-zinc-400">Thanh toán:</span>
                  <span
                    className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-mono font-bold border ${paymentBadge.className}`}
                  >
                    <ShieldCheck className="w-3.5 h-3.5" />
                    <span>{paymentBadge.label}</span>
                  </span>
                </div>
              </div>

              {/* Action Toolbar */}
              <div className="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-zinc-800">
                <div className="text-xs text-zinc-400">
                  {hasGHN ? (
                    <span className="flex items-center gap-1.5 text-sky-400 font-mono">
                      <Truck className="w-4 h-4" /> Vận đơn: <b>{selectedOrder.ghnTrackingCode}</b>
                    </span>
                  ) : isCancelled ? (
                    <span className="text-red-400 font-bold">Đơn hàng đã hủy</span>
                  ) : (
                    <span className="text-amber-400">Đơn hàng chờ tạo GHN</span>
                  )}
                </div>

                <div className="flex items-center gap-2">
                  {/* If pending and no GHN yet */}
                  {isPending && (
                    <>
                      <button
                        type="button"
                        onClick={() => handleCancelOrder(selectedOrder.id)}
                        className="px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-red-500/20 text-red-400 hover:text-red-300 border border-transparent hover:border-red-500/30 text-xs font-bold transition"
                      >
                        Hủy đơn hàng
                      </button>
                      <button
                        type="button"
                        onClick={() => handleCreateGHNOrder(selectedOrder)}
                        className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-lime-400 hover:bg-lime-300 text-zinc-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-lime-400/20 transition hover:scale-105"
                      >
                        <Truck className="w-4 h-4 stroke-[3]" />
                        <span>TẠO ĐƠN GHN</span>
                      </button>
                    </>
                  )}

                  {/* If shipping: Simulation button inside detail modal */}
                  {isShipping && (
                    <button
                      type="button"
                      onClick={() => handleSimulateGHNDelivered(selectedOrder.id)}
                      className="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-400 hover:bg-emerald-300 text-zinc-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-emerald-400/20 transition hover:scale-105"
                    >
                      <Zap className="w-4 h-4 stroke-[3]" />
                      <span>⚡ Giả lập Giao thành công</span>
                    </button>
                  )}

                  <button
                    type="button"
                    onClick={() => setSelectedOrder(null)}
                    className="px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-bold text-zinc-300 transition"
                  >
                    Đóng
                  </button>
                </div>
              </div>
            </div>
          </div>
        );
      })()}
    </div>
  );
};

export default Orders;
