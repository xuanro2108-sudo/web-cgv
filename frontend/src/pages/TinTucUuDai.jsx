
import { useEffect, useState } from "react";
import "./TinTucUuDai.css";

const API_URL = "http://127.0.0.1:8000/api";

function formatDate(dateString) {
    if (!dateString) return "";

    const date = new Date(dateString);

    if (Number.isNaN(date.getTime())) return dateString;

    return date.toLocaleDateString("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
}

function formatMoney(value) {
    const number = Number(value || 0);

    return number.toLocaleString("vi-VN") + "đ";
}

function getPromotionImage(path) {
    if (!path) {
        return "/banners/default-promotion.jpg";
    }

    // Nếu backend trả về URL đầy đủ
    if (path.startsWith("http://") || path.startsWith("https://")) {
        return path;
    }

    // Ảnh được Laravel lưu trong storage/app/public
    if (path.startsWith("/storage/")) {
        return `http://127.0.0.1:8000${path}`;
    }

    return `http://127.0.0.1:8000/storage/${path}`;
}

function getPromotionValue(promotion) {
    const value = Number(promotion.giaTri || 0);

    if (promotion.hinhThuc === "GIAM_PHAN_TRAM") {
        return `Giảm ${value}%`;
    }

    return `Giảm ${formatMoney(value)}`;
}

function TinTucUuDai() {
    const [promotions, setPromotions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        const loadPromotions = async () => {
            try {
                setLoading(true);
                setError("");

                const response = await fetch(
                    `${API_URL}/khuyen-mais`,
                    {
                        method: "GET",
                        headers: {
                            Accept: "application/json",
                        },
                    }
                );

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(
                        data.message ||
                            "Không thể tải danh sách ưu đãi."
                    );
                }

                setPromotions(data.data || []);
            } catch (err) {
                console.error("Lỗi tải ưu đãi:", err);

                setError(
                    err.message ||
                        "Không thể kết nối đến máy chủ."
                );
            } finally {
                setLoading(false);
            }
        };

        loadPromotions();
    }, []);

    return (
        <main className="promotion-page">
            <div className="promotion-container">

                {/* =========================
                    TIÊU ĐỀ
                ========================= */}
                <div className="promotion-heading">
                    <h1>TIN TỨC & ƯU ĐÃI</h1>
                    <p>
                        Cập nhật những chương trình ưu đãi
                        và thông tin mới nhất từ CGV.
                    </p>
                </div>


                {/* =========================
                    ƯU ĐÃI
                ========================= */}
                <section className="promotion-section">

                    <div className="section-title">
                        <h2>ƯU ĐÃI ĐANG DIỄN RA</h2>
                    </div>

                    {loading && (
                        <div className="promotion-message">
                            Đang tải ưu đãi...
                        </div>
                    )}

                    {!loading && error && (
                        <div className="promotion-message error">
                            {error}
                        </div>
                    )}

                    {!loading &&
                        !error &&
                        promotions.length === 0 && (
                            <div className="promotion-message">
                                Hiện chưa có chương trình ưu đãi nào.
                            </div>
                        )}

                    {!loading &&
                        !error &&
                        promotions.length > 0 && (
                            <div className="promotion-grid">
                                {promotions.map((promotion) => (
                                    <article
                                        className="promotion-card"
                                        key={promotion.maKM}
                                    >
                                        <div className="promotion-image">
                                            <img
                                                src={getPromotionImage(
                                                    promotion.hinhAnh
                                                )}
                                                alt={
                                                    promotion.tenKM
                                                }
                                                onError={(event) => {
                                                    event.currentTarget.src =
                                                        "/banners/default-promotion.jpg";
                                                }}
                                            />
                                        </div>

                                        <div className="promotion-content">
                                            <h3>
                                                {promotion.tenKM}
                                            </h3>

                                            <div className="promotion-value">
                                                {getPromotionValue(
                                                    promotion
                                                )}
                                            </div>

                                            {Number(
                                                promotion.donToiThieu
                                            ) > 0 && (
                                                <p className="promotion-condition">
                                                    Đơn tối thiểu{" "}
                                                    {formatMoney(
                                                        promotion.donToiThieu
                                                    )}
                                                </p>
                                            )}

                                            <div className="promotion-date">
                                                <span>
                                                    {formatDate(
                                                        promotion.ngayBatDau
                                                    )}
                                                </span>

                                                <span className="date-separator">
                                                    -
                                                </span>

                                                <span>
                                                    {formatDate(
                                                        promotion.ngayKetThuc
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        )}
                </section>


                {/* =========================
                    TIN TỨC
                ========================= */}
                <section className="news-section">

                    <div className="section-title">
                        <h2>TIN TỨC</h2>
                    </div>

                    <div className="news-empty">
                        <h3>Thông tin mới nhất</h3>

                        <p>
                            Các tin tức và thông báo mới từ
                            CGV sẽ được cập nhật tại đây.
                        </p>
                    </div>

                </section>

            </div>
        </main>
    );
}

export default TinTucUuDai;

