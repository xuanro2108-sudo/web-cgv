import { useEffect, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import "./HomeCustomer.css";
import { getMovies } from "../services/movieService";

const bannerImages = [
    "/banners/banner1.jpg",
    "/banners/banner2.png",
    "/banners/banner3.jpg",
];

function HomeCustomer() {
    const navigate = useNavigate();
    const [movies, setMovies] = useState([]);
    const [activeMovieTab, setActiveMovieTab] = useState("showing");
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [selectedMovie, setSelectedMovie] = useState(null);
    const [activeSlide, setActiveSlide] = useState(0);
    const touchStartX = useRef(null);

    const getYoutubeEmbedUrl = (url) => {
        if (!url) {
            return null;
        }

        try {
            const parsedUrl = new URL(url);
            let videoId = parsedUrl.searchParams.get("v");

            if (!videoId && parsedUrl.hostname === "youtu.be") {
                videoId = parsedUrl.pathname.slice(1);
            }

            return videoId
                ? `https://www.youtube.com/embed/${videoId}?autoplay=1`
                : null;
        } catch {
            return null;
        }
    };

    useEffect(() => {
        const loadMovies = async () => {
            try {
                setLoading(true);
                setError("");

                const data = await getMovies();
                setMovies(Array.isArray(data) ? data : data.data || []);
            } catch (err) {
                console.error("Lỗi tải danh sách phim:", err);
                setError("Không thể tải danh sách phim.");
            } finally {
                setLoading(false);
            }
        };

        loadMovies();
    }, []);

    const visibleMovies = movies.filter((movie) => {
        if (activeMovieTab === "upcoming") {
            return movie.trangThai === "SAP_CHIEU";
        }

        if (activeMovieTab === "showing") {
            return movie.trangThai !== "SAP_CHIEU";
        }

        return true;
    });

    const slideCount = bannerImages.length;
    const currentSlide = slideCount ? activeSlide % slideCount : 0;

    useEffect(() => {
        if (slideCount < 2) {
            return undefined;
        }

        const timer = window.setInterval(() => {
            setActiveSlide((current) => (current + 1) % slideCount);
        }, 5000);

        return () => window.clearInterval(timer);
    }, [slideCount]);

    return (
        <div className="home-customer">
            <section
                className="home-hero"
                onTouchStart={(event) => {
                    touchStartX.current = event.touches[0].clientX;
                }}
                onTouchEnd={(event) => {
                    if (touchStartX.current === null || slideCount < 2) return;
                    const distance = event.changedTouches[0].clientX - touchStartX.current;
                    if (Math.abs(distance) > 45) {
                        setActiveSlide((current) =>
                            (current + (distance < 0 ? 1 : -1) + slideCount) % slideCount
                        );
                    }
                    touchStartX.current = null;
                }}
            >
                {bannerImages.map((banner, index) => (
                    <div
                        className={`hero-slide ${index === currentSlide ? "active" : ""}`}
                        key={banner}
                        style={{ backgroundImage: `url("${banner}")` }}
                        aria-hidden={index !== currentSlide}
                    />
                ))}
                {slideCount > 1 && (
                    <>
                        <button
                            type="button"
                            className="hero-control hero-control-prev"
                            onClick={() =>
                                setActiveSlide((current) => (current - 1 + slideCount) % slideCount)
                            }
                            aria-label="Ảnh trước"
                        >
                            ‹
                        </button>
                        <button
                            type="button"
                            className="hero-control hero-control-next"
                            onClick={() =>
                                setActiveSlide((current) => (current + 1) % slideCount)
                            }
                            aria-label="Ảnh tiếp theo"
                        >
                            ›
                        </button>
                        <div className="hero-dots" aria-label="Chọn ảnh banner">
                            {bannerImages.map((banner, index) => (
                                <button
                                    type="button"
                                    className={index === currentSlide ? "active" : ""}
                                    key={banner}
                                    onClick={() => setActiveSlide(index)}
                                    aria-label={`Xem banner ${index + 1}`}
                                />
                            ))}
                        </div>
                    </>
                )}
            </section>

            <section className="home-movies">
                <div className="movie-content-layout">
                    <aside className="side-banner side-banner-left" aria-label="Banner bên trái" />
                    <div className="movie-content">
                <div
                    className="movie-tabs"
                    role="tablist"
                    aria-label="Danh mục phim"
                >
                    <button
                        className={activeMovieTab === "upcoming" ? "active" : ""}
                        onClick={() => setActiveMovieTab("upcoming")}
                        type="button"
                    >
                        PHIM SẮP CHIẾU
                    </button>
                    <button
                        className={activeMovieTab === "showing" ? "active" : ""}
                        onClick={() => setActiveMovieTab("showing")}
                        type="button"
                    >
                        PHIM ĐANG CHIẾU
                    </button>
                    <button
                        className={activeMovieTab === "all" ? "active" : ""}
                        onClick={() => setActiveMovieTab("all")}
                        type="button"
                    >
                        TẤT CẢ PHIM
                    </button>
                </div>

                {loading && (
                    <p className="movie-message">Đang tải danh sách phim...</p>
                )}
                {!loading && error && <p className="movies-error">{error}</p>}
                {!loading && !error && visibleMovies.length === 0 && (
                    <p className="movie-message">
                        Hiện chưa có phim trong danh mục này.
                    </p>
                )}

                {!loading && !error && visibleMovies.length > 0 && (
                    <div className="movie-list">
                        {visibleMovies.map((movie) => (
                            <article className="movie-card" key={movie.maPhim}>
                                <button
                                    type="button"
                                    className="movie-poster-button"
                                    onClick={() => setSelectedMovie(movie)}
                                    aria-label={`Xem trailer ${movie.tenPhim}`}
                                >
                                    {movie.hinhAnh ? (
                                        <img
                                            src={movie.hinhAnh}
                                            alt={movie.tenPhim}
                                            className="movie-poster"
                                        />
                                    ) : (
                                        <div className="movie-poster movie-poster-empty">
                                            Chưa có ảnh
                                        </div>
                                    )}
                                    <span className="movie-play-button" aria-hidden="true">
                                        ▶
                                    </span>
                                </button>
                                <div className="movie-card-content">
                                    <h3>{movie.tenPhim}</h3>
                                    {movie.theLoai && <p>{movie.theLoai}</p>}
                                    {movie.thoiLuong && (
                                        <span>{movie.thoiLuong} phút</span>
                                    )}
                                    <button
                                        type="button"
                                        className="buy-ticket-button"
                                        onClick={() => {
                                            if (
                                                localStorage.getItem("token") &&
                                                localStorage.getItem("vaiTro") === "KHACH_HANG"
                                            ) {
                                                navigate(`/dat-ve/${movie.maPhim}`);
                                                return;
                                            }

                                            navigate("/login", {
                                                state: { from: `/dat-ve/${movie.maPhim}` },
                                            });
                                        }}
                                    >
                                        <span aria-hidden="true">▣</span>
                                        MUA VÉ
                                    </button>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
                    </div>
                    <aside className="side-banner side-banner-right" aria-label="Banner bên phải" />
                </div>
            </section>

            {selectedMovie && (
                <div
                    className="trailer-modal-backdrop"
                    role="presentation"
                    onClick={() => setSelectedMovie(null)}
                >
                    <section
                        className="trailer-modal"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="trailer-title"
                        onClick={(event) => event.stopPropagation()}
                    >
                        <button
                            type="button"
                            className="trailer-close"
                            onClick={() => setSelectedMovie(null)}
                            aria-label="Đóng trailer"
                        >
                            ×
                        </button>
                        <h2 id="trailer-title">
                            TRAILER - {selectedMovie.tenPhim}
                        </h2>
                        {getYoutubeEmbedUrl(selectedMovie.trailer) ? (
                            <div className="trailer-frame">
                                <iframe
                                    src={getYoutubeEmbedUrl(selectedMovie.trailer)}
                                    title={`Trailer ${selectedMovie.tenPhim}`}
                                    allow="autoplay; encrypted-media; picture-in-picture"
                                    allowFullScreen
                                />
                            </div>
                        ) : (
                            <div className="trailer-unavailable">
                                <p>Trailer chưa có link YouTube hợp lệ.</p>
                                {selectedMovie.trailer && (
                                    <a
                                        href={selectedMovie.trailer}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        Mở link trailer
                                    </a>
                                )}
                            </div>
                        )}
                    </section>
                </div>
            )}
        </div>
    );
}

export default HomeCustomer;
