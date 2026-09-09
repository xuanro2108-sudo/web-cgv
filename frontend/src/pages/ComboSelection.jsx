import { useCallback, useEffect, useMemo, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import {
    addCombo,
    applyPromotion,
    getCombos,
    getCustomerProfile,
    getOrder,
    getPayment,
    getPromotions,
    createPayment,
    removeCombo,
    removePromotion,
    updateCombo,
} from "../services/movieService";
import "./ComboSelection.css";

function formatShowtime(date, time) {
    const datePart = String(date || "").match(/^\d{4}-\d{2}-\d{2}/)?.[0];
    const formattedDate = datePart
        ? datePart.split("-").reverse().join("/")
        : "Đang cập nhật";
    const formattedTime = String(time || "").slice(0, 5);

    return `${formattedDate}${formattedTime ? ` · ${formattedTime}` : ""}`;
}

function ComboSelection() {
    const { maDonHang } = useParams();
    const navigate = useNavigate();
    const [combos, setCombos] = useState([]);
    const [promotions, setPromotions] = useState([]);
    const [order, setOrder] = useState(null);
    const [quantities, setQuantities] = useState({});
    const [selectedPromotionCode, setSelectedPromotionCode] = useState("");
    const [secondsLeft, setSecondsLeft] = useState(600);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [savingCombo, setSavingCombo] = useState("");
    const [savingPromotion, setSavingPromotion] = useState(false);
    const [profile, setProfile] = useState(null);
    const [paymentMethod] = useState("SEPAY_QR");
    const [payment, setPayment] = useState(null);
    const [paying, setPaying] = useState(false);
    const [paymentSuccess, setPaymentSuccess] = useState(false);

    const loadOrder = useCallback(async () => {
        const response = await getOrder(maDonHang);
        const orderData = response.data;
        const comboLines =
            orderData.chi_tiet_combo_don_hangs ||
            orderData.chiTietComboDonHangs ||
            [];
        const tickets = orderData.ve_ghes || orderData.veGhes || [];
        const normalizedOrder = {
            ...orderData,
            comboLines,
            tickets,
        };

        setOrder(normalizedOrder);
        setSelectedPromotionCode(
            orderData.maKM || orderData.khuyenMai?.maKM || orderData.khuyen_mai?.maKM || ""
        );
        setQuantities(
            Object.fromEntries(
                comboLines.map((line) => [
                    line.maCombo,
                    line.soLuong,
                ])
            )
        );
    }, [maDonHang]);

    useEffect(() => {
        const loadData = async () => {
            try {
                const [comboResponse, promotionResponse, , profileResponse] = await Promise.all([
                    getCombos(),
                    getPromotions(),
                    loadOrder(),
                    getCustomerProfile(),
                ]);
                setCombos(Array.isArray(comboResponse) ? comboResponse : comboResponse.data || []);
                setPromotions(
                    Array.isArray(promotionResponse)
                        ? promotionResponse
                        : promotionResponse.data || []
                );
                setProfile(profileResponse.data);
            } catch (loadError) {
                console.error("Lỗi tải combo:", loadError);
                setError(loadError.message);
            } finally {
                setLoading(false);
            }
        };

        loadData();
    }, [loadOrder]);

    useEffect(() => {
        const timer = window.setInterval(() => {
            setSecondsLeft((current) => {
                if (current <= 1) {
                    window.clearInterval(timer);
                    navigate("/home", { replace: true });
                    return 0;
                }
                return current - 1;
            });
        }, 1000);

        return () => window.clearInterval(timer);
    }, [navigate]);

    useEffect(() => {
        if (paymentSuccess) {
            const redirectTimer = window.setTimeout(() => {
                navigate("/home", { replace: true });
            }, 3000);

            return () => window.clearTimeout(redirectTimer);
        }

        if (!payment || payment.trangThai !== "CHO_THANH_TOAN") {
            return undefined;
        }

        const timer = window.setInterval(async () => {
            try {
                const response = await getPayment(maDonHang);
                const nextPayment = response.data;
                setPayment(nextPayment);
                if (nextPayment.trangThai === "THANH_CONG") setPaymentSuccess(true);
            } catch (paymentError) {
                console.error("Lỗi kiểm tra thanh toán:", paymentError);
            }
        }, 3000);

        return () => window.clearInterval(timer);
    }, [maDonHang, navigate, payment, paymentSuccess]);

    const changeQuantity = async (combo, delta) => {
        const current = quantities[combo.maCombo] || 0;
        const next = current + delta;
        if (next < 0) return;

        setError("");
        setSavingCombo(combo.maCombo);
        try {
            if (current === 0 && next > 0) {
                await addCombo(maDonHang, combo.maCombo, next);
            } else if (next === 0) {
                await removeCombo(maDonHang, combo.maCombo);
            } else {
                await updateCombo(maDonHang, combo.maCombo, next);
            }
            await loadOrder();
        } catch (changeError) {
            setError(changeError.message);
        } finally {
            setSavingCombo("");
        }
    };

    const selectPromotion = async (maKM) => {
        setError("");
        setSavingPromotion(true);
        try {
            setSelectedPromotionCode(maKM);
            if (maKM) {
                const response = await applyPromotion(maDonHang, maKM);
                const promotionResult = response.data;
                setOrder((current) => ({
                    ...current,
                    maKM: promotionResult.maKM,
                    tongTien: promotionResult.tongTien,
                }));
            } else {
                await removePromotion(maDonHang);
            }
            await loadOrder();
        } catch (promotionError) {
            setError(promotionError.message);
        } finally {
            setSavingPromotion(false);
        }
    };

    const selectedTotal = useMemo(
        () => Number(order?.tongTien || 0).toLocaleString("vi-VN"),
        [order]
    );
    const ticketTotal = useMemo(
        () =>
            (order?.tickets || []).reduce(
                (sum, ticket) => sum + Number(ticket.giaVe || 0),
                0
            ),
        [order]
    );
    const comboTotal = useMemo(
        () =>
            (order?.comboLines || []).reduce(
                (sum, line) => sum + Number(line.thanhTien || 0),
                0
            ),
        [order]
    );
    const subtotal = ticketTotal + comboTotal;
    const eligiblePromotions = promotions.filter(
        (promotion) => subtotal >= Number(promotion.donToiThieu || 0)
    );
    const discountTotal = Math.max(0, subtotal - Number(order?.tongTien || subtotal));
    const firstTicket = order?.tickets?.[0];
    const showtime = firstTicket?.lich_chieu || firstTicket?.lichChieu;
    const movie = showtime?.phim;
    const selectedSeats = (order?.tickets || [])
        .map((ticket) => ticket.ghe?.hang && `${ticket.ghe.hang}${ticket.ghe.cot}`)
        .filter(Boolean)
        .join(", ");

    const completePayment = async () => {
        setError("");
        setPaying(true);
        try {
            const response = await createPayment(maDonHang, paymentMethod);
            setPayment(response.data);
            setPaymentSuccess(response.data.trangThai === "THANH_CONG");
        } catch (paymentError) {
            setError(paymentError.message);
        } finally {
            setPaying(false);
        }
    };
    if (loading) return <p className="combo-message">Đang tải combo...</p>;

    return (
        <div className="combo-page">
            <div className="combo-container">
                <div className="checkout-grid">
                    <main className="checkout-main">
                        <section className="checkout-section customer-section">
                            <h1>Thông tin thanh toán</h1>
                            <div className="customer-grid">
                                <div><span>Họ tên</span><strong>{profile?.hoTen || "Đang cập nhật"}</strong></div>
                                <div><span>Số điện thoại</span><strong>{profile?.soDienThoai || "Đang cập nhật"}</strong></div>
                                <div><span>Email</span><strong>{profile?.email || "Đang cập nhật"}</strong></div>
                            </div>
                        </section>
                        <section className="checkout-section">
                            <h2>Combo bắp nước</h2>
                            <p className="combo-subtitle">Chọn combo yêu thích nếu bạn có nhu cầu</p>
                            {error && <p className="combo-error">{error}</p>}
                            <div className="combo-list">
                            {combos.map((combo) => (
                                <article className="combo-card" key={combo.maCombo}>
                                    {combo.hinhAnh ? (
                                        <img src={combo.hinhAnh} alt={combo.tenCombo} />
                                    ) : (
                                        <div className="combo-image-placeholder">COMBO</div>
                                    )}
                                    <div className="combo-info">
                                        <h2>{combo.tenCombo}</h2>
                                        <p>{combo.moTa || "Combo bắp nước tiện lợi cho buổi xem phim."}</p>
                                        <strong>{Number(combo.donGia).toLocaleString("vi-VN")} đ</strong>
                                    </div>
                                    <div className="combo-quantity">
                                        <button
                                            type="button"
                                            disabled={savingCombo === combo.maCombo}
                                            onClick={() => changeQuantity(combo, -1)}
                                        >
                                            -
                                        </button>
                                        <b>{quantities[combo.maCombo] || 0}</b>
                                        <button
                                            type="button"
                                            disabled={savingCombo === combo.maCombo}
                                            onClick={() => changeQuantity(combo, 1)}
                                        >
                                            +
                                        </button>
                                    </div>
                                </article>
                            ))}
                            </div>
                        </section>
                        <section className="checkout-section promotion-box">
                            <h2>Chọn khuyến mại</h2>
                            <select
                                className="promotion-select"
                                value={selectedPromotionCode}
                                disabled={savingPromotion}
                                onChange={(event) => selectPromotion(event.target.value)}
                            >
                                <option value="">Không áp dụng</option>
                                {eligiblePromotions.map((promotion) => (
                                    <option key={promotion.maKM} value={promotion.maKM}>
                                        {promotion.tenKM} -{" "}
                                        {promotion.hinhThuc === "GIAM_PHAN_TRAM"
                                            ? `Giảm ${promotion.giaTri}%`
                                            : `Giảm ${Number(promotion.giaTri).toLocaleString("vi-VN")} đ`}
                                    </option>
                                ))}
                            </select>
                            {!eligiblePromotions.length && (
                                <p className="promotion-empty">Đơn hàng chưa đạt điều kiện khuyến mại.</p>
                            )}
                        </section>
                        <section className="checkout-section payment-method-box">
                            <h2>Phương thức thanh toán</h2>
                            <div className="payment-option active">
                                <span><strong>Thanh toán online bằng mã QR</strong></span>
                            </div>
                            {paymentSuccess && (
                                <div className="payment-success" role="status">
                                    <strong>Thanh toán thành công</strong>
                                    <span>Vé và mã QR đã được gửi về email {profile?.email || "của bạn"}.</span>
                                </div>
                            )}
                            {payment?.qrUrl && !paymentSuccess && (
                                <div className="sepay-qr-box">
                                    <img src={payment.qrUrl} alt="Mã QR thanh toán" />
                                    <strong>{Number(payment.soTien).toLocaleString("vi-VN")} đ</strong>
                                    <span>Nội dung chuyển khoản: <b>{payment.transferContent}</b></span>
                                </div>
                            )}
                        </section>
                    </main>
                    <aside className="combo-summary checkout-summary">
                        <div className="movie-summary">
                            {movie?.hinhAnh ? (
                                <img src={movie.hinhAnh} alt={movie.tenPhim} />
                            ) : (
                                <div className="movie-summary-placeholder">CGV</div>
                            )}
                            <div>
                                <h3>{movie?.tenPhim || "Đang cập nhật"}</h3>
                                <p>{movie?.theLoai || "Đang cập nhật"}</p>
                                <p>{movie?.thoiLuong ? `${movie.thoiLuong} phút` : "Thời lượng đang cập nhật"}</p>
                            </div>
                        </div>
                        <div className="ticket-summary">
                            <div><span>Suất chiếu</span><strong>{formatShowtime(showtime?.ngayChieu, showtime?.gioBatDau)}</strong></div>
                            <div><span>Phòng chiếu</span><strong>{showtime?.phong_chieu?.tenPhong || showtime?.phongChieu?.tenPhong || "Đang cập nhật"}</strong></div>
                            <div><span>Ghế ngồi</span><strong>{selectedSeats || "Đang cập nhật"}</strong></div>
                        </div>
                        <div className="combo-breakdown">
                            <span>Tiền vé</span>
                            <b>{ticketTotal.toLocaleString("vi-VN")} đ</b>
                            <span>Tiền combo</span>
                            <b>{comboTotal.toLocaleString("vi-VN")} đ</b>
                            <span>Giảm giá</span>
                            <b className="discount-amount">-{discountTotal.toLocaleString("vi-VN")} đ</b>
                        </div>
                        <div className="combo-total">
                            <span>Tổng tiền</span>
                            <strong>{selectedTotal} đ</strong>
                        </div>
                        <div className="combo-timer">
                            Thời gian còn lại
                            <b>{Math.floor(secondsLeft / 60)}:{String(secondsLeft % 60).padStart(2, "0")}</b>
                        </div>
                        <button type="button" className="continue-button" disabled={paying || Boolean(payment)} onClick={completePayment}>
                            {paying ? "ĐANG TẠO MÃ QR..." : paymentSuccess ? "ĐÃ THANH TOÁN" : payment ? "ĐANG CHỜ THANH TOÁN" : "TẠO MÃ QR THANH TOÁN"}
                        </button>
                    </aside>
                </div>
            </div>
        </div>
    );
}

export default ComboSelection;
