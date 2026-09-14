
import { useEffect, useState } from "react";
import QRCode from "react-qr-code";
import "./MyTickets.css";

function formatDate(date) {
    if (!date) return "N/A";

    const d = new Date(date);

    if (Number.isNaN(d.getTime())) {
        return date;
    }

    return d.toLocaleDateString("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
}

function formatTime(time) {
    if (!time) return "N/A";

    return String(time).substring(0, 5);
}

function formatMoney(value) {
    return Number(value || 0).toLocaleString("vi-VN") + "đ";
}

function formatSeat(seat) {
    if (!seat) return "N/A";

    if (
        seat.hang !== undefined &&
        seat.cot !== undefined
    ) {
        return `${seat.hang}${seat.cot}`;
    }

    return seat.maGhe || "N/A";
}

function MyTickets() {
    const [orders, setOrders] = useState([]);
    const [selectedOrder, setSelectedOrder] = useState(null);
    const [filter, setFilter] = useState("all");

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        const loadOrders = async () => {
            try {
                setLoading(true);
                setError("");

                const token = localStorage.getItem("token");

                const response = await fetch(
                    "http://localhost:8000/api/my-orders",
                    {
                        headers: {
                            Authorization: `Bearer ${token}`,
                            Accept: "application/json",
                        },
                    }
                );

                if (!response.ok) {
                    throw new Error(
                        "Không thể tải danh sách đơn hàng"
                    );
                }

                const result = await response.json();

                setOrders(
                    Array.isArray(result)
                        ? result
                        : result.data || []
                );
            } catch (err) {
                console.error(err);
                setError(
                    "Không thể tải danh sách đơn hàng."
                );
            } finally {
                setLoading(false);
            }
        };

        loadOrders();
    }, []);

    /*
     * LỌC ĐƠN HÀNG
     *
     * all:
     *   Hiển thị tất cả đơn có vé hợp lệ
     *
     * booked:
     *   Có ít nhất 1 vé DA_DAT
     *
     * used:
     *   Có ít nhất 1 vé DA_SU_DUNG
     */
    const filteredOrders = orders.filter((order) => {
        const tickets = order.ve_ghes || [];

        if (filter === "booked") {
            return tickets.some(
                (ticket) => ticket.trangThai === "DA_DAT"
            );
        }

        if (filter === "used") {
            return tickets.some(
                (ticket) => ticket.trangThai === "DA_SU_DUNG"
            );
        }

        return true;
    });

    if (loading) {
        return (
            <div className="my-tickets-page">
                <h1>VÉ CỦA TÔI</h1>

                <div className="tickets-loading">
                    Đang tải đơn hàng...
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="my-tickets-page">
                <h1>VÉ CỦA TÔI</h1>

                <div className="tickets-error">
                    {error}
                </div>
            </div>
        );
    }

    return (
        <div className="my-tickets-page">
            <h1>VÉ CỦA TÔI</h1>

            {/* FILTER */}
            <div className="ticket-filter">
                <button
                    className={
                        filter === "all" ? "active" : ""
                    }
                    onClick={() => setFilter("all")}
                >
                    Tất cả
                </button>

                <button
                    className={
                        filter === "booked" ? "active" : ""
                    }
                    onClick={() => setFilter("booked")}
                >
                    Đã đặt
                </button>

                <button
                    className={
                        filter === "used" ? "active" : ""
                    }
                    onClick={() => setFilter("used")}
                >
                    Đã sử dụng
                </button>
            </div>

            {/* EMPTY */}
            {filteredOrders.length === 0 ? (
                <div className="empty-tickets">
                    <div className="empty-icon">
                        🎟️
                    </div>

                    <h2>
                        {filter === "booked"
                            ? "Chưa có vé chưa sử dụng"
                            : filter === "used"
                            ? "Chưa có vé đã sử dụng"
                            : "Chưa có đơn hàng"}
                    </h2>

                    <p>
                        {filter === "booked"
                            ? "Bạn chưa có vé nào đang chờ sử dụng."
                            : filter === "used"
                            ? "Bạn chưa có vé nào đã sử dụng."
                            : "Bạn chưa có đơn hàng nào đã thanh toán."}
                    </p>
                </div>
            ) : (
                /* ORDER LIST */
                <div className="my-orders-list">
                    {filteredOrders.map((order) => {
                        const firstTicket =
                            order.ve_ghes?.[0];

                        const schedule =
                            firstTicket?.lich_chieu;

                        const movie =
                            schedule?.phim;

                        const room =
                            schedule?.phong_chieu;

                        return (
                            <div
                                className="my-order-card"
                                key={order.maDonHang}
                            >
                                {/* LEFT - MOVIE */}
                                <div className="order-movie">
                                    {movie?.hinhAnh ? (
                                        <img
                                            src={movie.hinhAnh}
                                            alt={movie.tenPhim}
                                        />
                                    ) : (
                                        <div className="movie-placeholder">
                                            🎬
                                        </div>
                                    )}
                                </div>

                                {/* CENTER - INFORMATION */}
                                <div className="order-information">
                                    <div className="order-header">
                                        <span>
                                            ĐƠN HÀNG #
                                            {order.maDonHang}
                                        </span>
                                    </div>

                                    <h2>
                                        {movie?.tenPhim ||
                                            "Không xác định"}
                                    </h2>

                                    <div className="show-information">
                                        <div>
                                            <span className="info-icon">
                                                📅
                                            </span>

                                            <div>
                                                <small>
                                                    Ngày chiếu
                                                </small>

                                                <strong>
                                                    {formatDate(
                                                        schedule?.ngayChieu
                                                    )}
                                                </strong>
                                            </div>
                                        </div>

                                        <div>
                                            <span className="info-icon">
                                                🕐
                                            </span>

                                            <div>
                                                <small>
                                                    Giờ chiếu
                                                </small>

                                                <strong>
                                                    {formatTime(
                                                        schedule?.gioBatDau
                                                    )}
                                                </strong>
                                            </div>
                                        </div>

                                        <div>
                                            <span className="info-icon">
                                                🏢
                                            </span>

                                            <div>
                                                <small>
                                                    Phòng
                                                </small>

                                                <strong>
                                                    {room?.tenPhong ||
                                                        room?.maPhong ||
                                                        "N/A"}
                                                </strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="order-footer">
                                        <span>
                                            {order.ve_ghes?.length ||
                                                0}{" "}
                                            vé
                                        </span>

                                        <span>
                                            {order
                                                .chi_tiet_combo_don_hangs
                                                ?.length || 0}{" "}
                                            combo
                                        </span>

                                        <strong>
                                            {formatMoney(
                                                order.tongTien
                                            )}
                                        </strong>
                                    </div>

                                    <button
                                        className="detail-button"
                                        onClick={() =>
                                            setSelectedOrder(order)
                                        }
                                    >
                                        Xem chi tiết
                                    </button>
                                </div>

                                {/* RIGHT - QR */}
                                <div className="order-qr">
                                    <div className="qr-wrapper">
                                        <QRCode
                                            value={
                                                order.maQR ||
                                                order.maDonHang
                                            }
                                            size={140}
                                        />
                                    </div>

                                    <span>
                                        Quét mã tại rạp
                                    </span>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            {/* DETAIL MODAL */}
            {selectedOrder && (
                <div
                    className="order-modal-overlay"
                    onClick={() =>
                        setSelectedOrder(null)
                    }
                >
                    <div
                        className="order-modal"
                        onClick={(event) =>
                            event.stopPropagation()
                        }
                    >
                        <button
                            className="modal-close"
                            onClick={() =>
                                setSelectedOrder(null)
                            }
                        >
                            ×
                        </button>

                        <div className="modal-title">
                            <h2>
                                Chi tiết đơn hàng
                            </h2>

                            <span>
                                #
                                {
                                    selectedOrder.maDonHang
                                }
                            </span>
                        </div>

                        {/* QR */}
                        <div className="modal-qr">
                            <QRCode
                                value={
                                    selectedOrder.maQR ||
                                    selectedOrder.maDonHang
                                }
                                size={210}
                            />

                            <p>
                                Đưa mã QR này cho nhân viên
                                khi vào rạp
                            </p>
                        </div>

                        {/* MOVIE */}
                        {(() => {
                            const ticket =
                                selectedOrder.ve_ghes?.[0];

                            const schedule =
                                ticket?.lich_chieu;

                            const movie =
                                schedule?.phim;

                            const room =
                                schedule?.phong_chieu;

                            return (
                                <div className="modal-show-info">
                                    <h3>
                                        🎬 Thông tin suất chiếu
                                    </h3>

                                    <div className="show-detail-grid">
                                        <div>
                                            <span>
                                                Phim
                                            </span>

                                            <strong>
                                                {movie?.tenPhim ||
                                                    "N/A"}
                                            </strong>
                                        </div>

                                        <div>
                                            <span>
                                                Ngày chiếu
                                            </span>

                                            <strong>
                                                {formatDate(
                                                    schedule?.ngayChieu
                                                )}
                                            </strong>
                                        </div>

                                        <div>
                                            <span>
                                                Giờ chiếu
                                            </span>

                                            <strong>
                                                {formatTime(
                                                    schedule?.gioBatDau
                                                )}
                                            </strong>
                                        </div>

                                        <div>
                                            <span>
                                                Phòng
                                            </span>

                                            <strong>
                                                {room?.tenPhong ||
                                                    room?.maPhong ||
                                                    "N/A"}
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            );
                        })()}

                        {/* TICKETS */}
                        <div className="modal-section">
                            <div className="section-title">
                                <h3>
                                    🎟 Vé
                                </h3>

                                <span>
                                    {selectedOrder.ve_ghes
                                        ?.length || 0}
                                </span>
                            </div>

                            <div className="ticket-list">
                                {selectedOrder.ve_ghes?.map(
                                    (ticket) => (
                                        <div
                                            className="ticket-detail-row"
                                            key={ticket.maVe}
                                        >
                                            <div>
                                                <strong>
                                                    Ghế{" "}
                                                    {formatSeat(
                                                        ticket.ghe
                                                    )}
                                                </strong>

                                                <small>
                                                    Mã vé:{" "}
                                                    {
                                                        ticket.maVe
                                                    }
                                                </small>
                                            </div>

                                            <strong>
                                                {formatMoney(
                                                    ticket.giaVe
                                                )}
                                            </strong>
                                        </div>
                                    )
                                )}
                            </div>
                        </div>

                        {/* COMBOS */}
                        <div className="modal-section">
                            <div className="section-title">
                                <h3>
                                    🍿 Combo trong đơn hàng
                                </h3>

                                <span>
                                    {selectedOrder
                                        .chi_tiet_combo_don_hangs
                                        ?.length || 0}
                                </span>
                            </div>

                            {selectedOrder
                                .chi_tiet_combo_don_hangs
                                ?.length > 0 ? (
                                <div className="combo-list">
                                    {selectedOrder.chi_tiet_combo_don_hangs.map(
                                        (item) => (
                                            <div
                                                className="combo-detail-row"
                                                key={
                                                    item.maChiTiet
                                                }
                                            >
                                                <div>
                                                    <strong>
                                                        {item
                                                            .combo
                                                            ?.tenCombo ||
                                                            item.maCombo}
                                                    </strong>

                                                    <small>
                                                        Số lượng:{" "}
                                                        {
                                                            item.soLuong
                                                        }
                                                    </small>
                                                </div>

                                                <strong>
                                                    {formatMoney(
                                                        item.thanhTien
                                                    )}
                                                </strong>
                                            </div>
                                        )
                                    )}
                                </div>
                            ) : (
                                <div className="no-combo">
                                    Đơn hàng không có combo.
                                </div>
                            )}
                        </div>

                        {/* TOTAL */}
                        <div className="modal-total">
                            <span>
                                Tổng tiền
                            </span>

                            <strong>
                                {formatMoney(
                                    selectedOrder.tongTien
                                )}
                            </strong>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

export default MyTickets;

