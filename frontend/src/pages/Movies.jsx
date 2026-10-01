import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import "./Movies.css";

import { getMovies } from "../services/movieService";

function normalizeMovieSearch(value) {
    return String(value || "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[đĐ]/g, "d")
        .toLocaleLowerCase("vi")
        .trim();
}

function Movies() {
    const navigate = useNavigate();

    const [movies, setMovies] = useState([]);
    const [activeMovieTab, setActiveMovieTab] = useState("showing");
    const [searchQuery, setSearchQuery] = useState("");

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    const [selectedMovie, setSelectedMovie] = useState(null);

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

                setMovies(
                    Array.isArray(data)
                        ? data
                        : data.data || []
                );
            } catch (err) {
                console.error("Lỗi tải danh sách phim:", err);
                setError("Không thể tải danh sách phim.");
            } finally {
                setLoading(false);
            }
        };

        loadMovies();
    }, []);

    const normalizedSearchQuery = normalizeMovieSearch(searchQuery);
    const visibleMovies = movies.filter((movie) => {
        let matchesTab = true;

        if (activeMovieTab === "upcoming") {
            matchesTab = movie.trangThai === "SAP_CHIEU";
        } else if (activeMovieTab === "showing") {
            matchesTab = movie.trangThai !== "SAP_CHIEU";
        }

        const matchesSearch = normalizeMovieSearch(movie.tenPhim).includes(
            normalizedSearchQuery
        );

        return matchesTab && matchesSearch;
    });

    return (
        <div className="movies-page"><div className="cinema-page-heading"><span className="cinema-kicker">FIND YOUR NEXT STORY</span><h1>Mỗi bộ phim.<br />Một thế giới mới.</h1><p>Chọn câu chuyện bạn muốn bước vào hôm nay.</p></div>

            {/* TAB */}
            <div className="movie-tabs">
                <button
                    className={
                        activeMovieTab === "upcoming"
                            ? "active"
                            : ""
                    }
                    onClick={() =>
                        setActiveMovieTab("upcoming")
                    }
                    type="button"
                >
                    PHIM SẮP CHIẾU
                </button>

                <button
                    className={
                        activeMovieTab === "showing"
                            ? "active"
                            : ""
                    }
                    onClick={() =>
                        setActiveMovieTab("showing")
                    }
                    type="button"
                >
                    PHIM ĐANG CHIẾU
                </button>

                <button
                    className={
                        activeMovieTab === "all"
                            ? "active"
                            : ""
                    }
                    onClick={() =>
                        setActiveMovieTab("all")
                    }
                    type="button"
                >
                    TẤT CẢ PHIM
                </button>
            </div>

            <div className="movie-search">
                <input
                    type="search"
                    aria-label="Tìm phim"
                    placeholder="Nhập tên phim cần tìm..."
                    value={searchQuery}
                    onChange={(event) => setSearchQuery(event.target.value)}
                />
                {searchQuery && (
                    <button
                        type="button"
                        onClick={() => setSearchQuery("")}
                        aria-label="Xóa tìm kiếm"
                    >
                        Xóa
                    </button>
                )}
            </div>

            {/* LOADING */}
            {loading && (
                <p className="movie-message">
                    Đang tải danh sách phim...
                </p>
            )}

            {/* ERROR */}
            {!loading && error && (
                <p className="movies-error">
                    {error}
                </p>
            )}

            {/* KHÔNG CÓ PHIM */}
            {!loading &&
                !error &&
                !normalizedSearchQuery &&
                visibleMovies.length === 0 && (
                    <p className="movie-message">
                        Hiện chưa có phim trong danh mục này.
                    </p>
                )}

            {!loading &&
                !error &&
                normalizedSearchQuery &&
                visibleMovies.length === 0 && (
                    <p className="movie-message">
                        Không tìm thấy phim phù hợp.
                    </p>
                )}

            {/* DANH SÁCH PHIM */}
            {!loading &&
                !error &&
                visibleMovies.length > 0 && (

                    <div className="movie-list">

                        {visibleMovies.map((movie) => (

                            <article
                                className="movie-card"
                                key={movie.maPhim}
                            >

                                {/* POSTER */}
                                <button
                                    type="button"
                                    className="movie-poster-button"
                                    onClick={() =>
                                        setSelectedMovie(movie)
                                    }
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

                                    <span className="movie-play-button">
                                        ▶
                                    </span>
                                </button>

                                {/* THÔNG TIN */}
                                <div className="movie-card-content">

                                    <h3>
                                        {movie.tenPhim}
                                    </h3>

                                    {movie.theLoai && (
                                        <p>
                                            {movie.theLoai}
                                        </p>
                                    )}

                                    {movie.thoiLuong && (
                                        <span>
                                            {movie.thoiLuong} phút
                                        </span>
                                    )}

                                    <button
                                        type="button"
                                        className="buy-ticket-button"
                                        onClick={() => {

                                            if (
                                                localStorage.getItem("token") &&
                                                localStorage.getItem("vaiTro") === "KHACH_HANG"
                                            ) {
                                                navigate(
                                                    `/dat-ve/${movie.maPhim}`
                                                );

                                                return;
                                            }

                                            navigate("/login", {
                                                state: {
                                                    from: `/dat-ve/${movie.maPhim}`
                                                }
                                            });
                                        }}
                                    >
                                        <span>▣</span>
                                        MUA VÉ
                                    </button>

                                </div>

                            </article>

                        ))}

                    </div>
                )}

            {/* TRAILER */}
            {selectedMovie && (

                <div
                    className="trailer-modal-backdrop"
                    onClick={() =>
                        setSelectedMovie(null)
                    }
                >

                    <section
                        className="trailer-modal"
                        onClick={(event) =>
                            event.stopPropagation()
                        }
                    >

                        <button
                            type="button"
                            className="trailer-close"
                            onClick={() =>
                                setSelectedMovie(null)
                            }
                        >
                            ×
                        </button>

                        <h2>
                            TRAILER -{" "}
                            {selectedMovie.tenPhim}
                        </h2>

                        {getYoutubeEmbedUrl(
                            selectedMovie.trailer
                        ) ? (

                            <div className="trailer-frame">

                                <iframe
                                    src={getYoutubeEmbedUrl(
                                        selectedMovie.trailer
                                    )}
                                    title={`Trailer ${selectedMovie.tenPhim}`}
                                    allow="autoplay; encrypted-media; picture-in-picture"
                                    allowFullScreen
                                />

                            </div>

                        ) : (

                            <div className="trailer-unavailable">
                                <p>
                                    Trailer chưa có link YouTube hợp lệ.
                                </p>
                            </div>

                        )}

                    </section>

                </div>

            )}

        </div>
    );
}

export default Movies;
