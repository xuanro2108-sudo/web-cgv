import { useEffect, useMemo, useState } from "react";
import {
  collection,
  getSeatLabel,
  normalizeShowtimeDate,
  request,
} from "../utils/counterSaleUtils";

export default function useCounterSale() {
  // Wizard Step: 1 = PHIM, 2 = SUAT_CHIEU, 3 = GHE, 4 = COMBO, 5 = THANH_TOAN, 6 = HOAN_TAT
  const [step, setStep] = useState(1);

  // Data states
  const [movies, setMovies] = useState([]);
  const [allShowtimes, setAllShowtimes] = useState([]);
  const [combos, setCombos] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  // Step 1
  const [movieSearch, setMovieSearch] = useState("");
  const [movieTab, setMovieTab] = useState("all");
  const [selectedMovie, setSelectedMovie] = useState(null);

  // Step 2
  const [selectedDate, setSelectedDate] = useState("");
  const [selectedShowtime, setSelectedShowtime] = useState(null);
  const [showtimeDetail, setShowtimeDetail] = useState(null);
  const [loadingShowtime, setLoadingShowtime] = useState(false);

  // Step 3
  const [selectedSeats, setSelectedSeats] = useState([]);

  // Step 4
  const [selectedCombos, setSelectedCombos] = useState({});

  // Step 5
  const [customer, setCustomer] = useState({ hoTen: "", soDienThoai: "" });
  const [paymentMethod, setPaymentMethod] = useState("TIEN_MAT");
  const [paymentConfig, setPaymentConfig] = useState({
    bank: "TPBank",
    accountNumber: "21082005555",
    accountName: "TRAN THANH XUAN",
    template: "compact2",
  });
  const [submitting, setSubmitting] = useState(false);

  // Step 6
  const [pendingOrder, setPendingOrder] = useState(null);
  const [completedOrder, setCompletedOrder] = useState(null);

  // Initial fetch
  useEffect(() => {
    setLoading(true);
    setError("");

    Promise.all([
      request("/phims"),
      request("/lich-chieus"),
      request("/combos"),
      request("/thanh-toan/config").catch(() => null),
    ])
      .then(([movieBody, showtimeBody, comboBody, configBody]) => {
        setMovies(collection(movieBody));
        setAllShowtimes(collection(showtimeBody));
        setCombos(collection(comboBody));

        if (configBody?.data) {
          setPaymentConfig(configBody.data);
        }
      })
      .catch((err) => {
        console.error("Lỗi tải dữ liệu quầy vé:", err);
        setError("Không thể tải danh sách phim, suất chiếu và combo.");
      })
      .finally(() => setLoading(false));
  }, []);

  const filteredMovies = useMemo(() => {
    return movies.filter((movie) => {
      const matchSearch = (movie.tenPhim || "")
        .toLowerCase()
        .includes(movieSearch.toLowerCase());

      if (!matchSearch) return false;
      if (movieTab === "showing") return movie.trangThai !== "SAP_CHIEU";
      if (movieTab === "upcoming") return movie.trangThai === "SAP_CHIEU";
      return true;
    });
  }, [movies, movieSearch, movieTab]);

  const movieShowtimes = useMemo(() => {
    if (!selectedMovie) return [];

    return allShowtimes.filter(
      (item) =>
        item.maPhim === selectedMovie.maPhim &&
        item.trangThai === "HOAT_DONG"
    );
  }, [allShowtimes, selectedMovie]);

  const availableDates = useMemo(() => {
    return [
      ...new Set(
        movieShowtimes.map((item) => normalizeShowtimeDate(item.ngayChieu))
      ),
    ]
      .filter(Boolean)
      .sort();
  }, [movieShowtimes]);

  const dateShowtimes = useMemo(() => {
    if (!selectedDate) return [];

    return movieShowtimes.filter(
      (item) => normalizeShowtimeDate(item.ngayChieu) === selectedDate
    );
  }, [movieShowtimes, selectedDate]);

  const handleSelectMovie = (movie) => {
    setSelectedMovie(movie);

    const relatedShowtimes = allShowtimes.filter(
      (item) =>
        item.maPhim === movie.maPhim && item.trangThai === "HOAT_DONG"
    );

    const dates = [
      ...new Set(
        relatedShowtimes.map((item) => normalizeShowtimeDate(item.ngayChieu))
      ),
    ]
      .filter(Boolean)
      .sort();

    setSelectedDate(dates[0] || "");
    setSelectedShowtime(null);
    setShowtimeDetail(null);
    setSelectedSeats([]);
    setSelectedCombos({});
    setError("");
    setStep(2);
  };

  const handleSelectShowtime = async (showtime) => {
    setSelectedShowtime(showtime);
    setSelectedSeats([]);
    setLoadingShowtime(true);
    setError("");

    try {
      const detailResponse = await request(
        `/lich-chieus/${encodeURIComponent(showtime.maLichChieu)}`
      );
      setShowtimeDetail(detailResponse.data || detailResponse);
      setStep(3);
    } catch (err) {
      console.error("Lỗi tải sơ đồ ghế:", err);
      setError("Không thể tải sơ đồ ghế của suất chiếu này.");
    } finally {
      setLoadingShowtime(false);
    }
  };

  const seats = useMemo(() => {
    return (
      showtimeDetail?.phong_chieu?.so_do_ghe?.ghes ||
      showtimeDetail?.phongChieu?.soDoGhe?.ghes ||
      []
    );
  }, [showtimeDetail]);

  const rows = useMemo(() => {
    return seats.reduce((result, seat) => {
      const row = seat.hang || "?";
      result[row] = [...(result[row] || []), seat].sort(
        (a, b) => a.cot - b.cot
      );
      return result;
    }, {});
  }, [seats]);

  const seatGroups = useMemo(() => {
    return Object.entries(rows).map(([row, rowSeats]) => {
      const groups = [];

      rowSeats.forEach((seat) => {
        const previous = groups[groups.length - 1];
        const isPair =
          seat.loaiGhe === "DOI" &&
          previous?.length === 1 &&
          previous[0].loaiGhe === "DOI" &&
          previous[0].cot + 1 === seat.cot;

        if (isPair) {
          previous.push(seat);
        } else {
          groups.push([seat]);
        }
      });

      return [row, groups];
    });
  }, [rows]);

  const seatPrices = useMemo(() => {
    const basePrice = Number(
      showtimeDetail?.giaVeCoBan || selectedShowtime?.giaVeCoBan || 0
    );
    const vipPrice = basePrice * 1.2;

    return {
      THUONG: basePrice,
      VIP: vipPrice,
      DOI: vipPrice * 2,
    };
  }, [showtimeDetail, selectedShowtime]);

  const ticketTotal = useMemo(() => {
    const coupleSeatCount = selectedSeats.filter(
      (seat) => seat.loaiGhe === "DOI"
    ).length;

    const regularTotal = selectedSeats
      .filter((seat) => seat.loaiGhe !== "DOI")
      .reduce(
        (sum, seat) => sum + (seatPrices[seat.loaiGhe] || 0),
        0
      );

    return regularTotal + (coupleSeatCount / 2) * seatPrices.DOI;
  }, [selectedSeats, seatPrices]);

  const comboTotal = useMemo(() => {
    return Object.entries(selectedCombos).reduce(
      (total, [maCombo, qty]) => {
        const combo = combos.find((c) => c.maCombo === maCombo);
        return total + (combo ? Number(combo.donGia) * qty : 0);
      },
      0
    );
  }, [selectedCombos, combos]);

  const grandTotal = useMemo(
    () => ticketTotal + comboTotal,
    [ticketTotal, comboTotal]
  );

  const toggleSeatGroup = (seatGroup) => {
    if (seatGroup.some((seat) => seat.trangThai !== "HOAT_DONG")) return;

    const groupIds = seatGroup.map((seat) => seat.maGhe);

    setSelectedSeats((current) => {
      const isSelected = seatGroup.every((seat) =>
        current.some((item) => item.maGhe === seat.maGhe)
      );

      return isSelected
        ? current.filter((item) => !groupIds.includes(item.maGhe))
        : [...current, ...seatGroup];
    });
  };

  const updateComboQty = (maCombo, delta) => {
    setSelectedCombos((prev) => {
      const currentQty = prev[maCombo] || 0;
      const newQty = Math.max(0, currentQty + delta);

      if (newQty === 0) {
        const next = { ...prev };
        delete next[maCombo];
        return next;
      }

      return { ...prev, [maCombo]: newQty };
    });
  };

  const handleCheckout = async (e, forceConfirm = false) => {
    if (e && e.preventDefault) e.preventDefault();
    if (!selectedShowtime || !selectedSeats.length || submitting) return;

    if (!customer.hoTen.trim() || !customer.soDienThoai.trim()) {
      setError("Vui lòng nhập đầy đủ họ tên và số điện thoại khách hàng.");
      return;
    }

    setSubmitting(true);
    setError("");

    try {
      const seatIds = selectedSeats.map((seat) => seat.maGhe);
      const comboPayload = Object.entries(selectedCombos)
        .filter(([_, qty]) => qty > 0)
        .map(([maCombo, soLuong]) => ({ maCombo, soLuong }));

      const phoneDigits = customer.soDienThoai
        .trim()
        .replace(/\D/g, "");
      const transactionCode = `CGVPOS${phoneDigits}`;
      const autoWaitSePay = paymentMethod === "SEPAY_QR" && !forceConfirm;

      const response = await request("/quan-ly/ban-ve-tai-quay", {
        method: "POST",
        body: JSON.stringify({
          hoTen: customer.hoTen.trim(),
          soDienThoai: customer.soDienThoai.trim(),
          maLichChieu: selectedShowtime.maLichChieu,
          maGhes: seatIds,
          combos: comboPayload,
          phuongThuc: paymentMethod,
          maGiaoDich: transactionCode,
          xacNhanNgay: !autoWaitSePay,
        }),
      });

      const orderResult = response.data;

      if (autoWaitSePay && orderResult.trangThai === "CHO_THANH_TOAN") {
        setPendingOrder(orderResult);
      } else {
        setCompletedOrder(orderResult);
        setPendingOrder(null);
        setStep(6);
      }
    } catch (err) {
      console.error("Lỗi bán vé tại quầy:", err);
      setError(err.message || "Không thể hoàn tất bán vé.");
    } finally {
      setSubmitting(false);
    }
  };

  // Polling pending SePay order for automatic confirmation
  useEffect(() => {
    if (!pendingOrder || pendingOrder.trangThai === "DA_THANH_TOAN") {
      return undefined;
    }

    const timer = setInterval(async () => {
      try {
        const res = await request(
          `/quan-ly/don-hangs/${encodeURIComponent(pendingOrder.maDonHang)}`
        );
        const orderData = res.data || res;

        if (orderData?.trangThai === "DA_THANH_TOAN") {
          setCompletedOrder(orderData);
          setPendingOrder(null);
          setStep(6);
        }
      } catch (err) {
        console.error("Lỗi polling SePay:", err);
      }
    }, 2000);

    return () => clearInterval(timer);
  }, [pendingOrder]);

  // Auto-create pending order in DB for SePay QR listening
  useEffect(() => {
    if (
      step === 5 &&
      paymentMethod === "SEPAY_QR" &&
      customer.hoTen.trim() &&
      customer.soDienThoai.trim().length >= 9 &&
      !pendingOrder &&
      !submitting &&
      selectedShowtime &&
      selectedSeats.length
    ) {
      const timer = setTimeout(() => {
        handleCheckout(null, false);
      }, 500);

      return () => clearTimeout(timer);
    }
  }, [
    step,
    paymentMethod,
    customer.hoTen,
    customer.soDienThoai,
    pendingOrder,
    submitting,
    selectedShowtime,
    selectedSeats,
  ]);

  const handleReset = () => {
    setStep(1);
    setSelectedMovie(null);
    setSelectedDate("");
    setSelectedShowtime(null);
    setShowtimeDetail(null);
    setSelectedSeats([]);
    setSelectedCombos({});
    setCustomer({ hoTen: "", soDienThoai: "" });
    setPaymentMethod("TIEN_MAT");
    setPendingOrder(null);
    setCompletedOrder(null);
    setError("");
  };

  return {
    step,
    setStep,
    movies,
    allShowtimes,
    combos,
    loading,
    error,
    setError,
    movieSearch,
    setMovieSearch,
    movieTab,
    setMovieTab,
    selectedMovie,
    selectedDate,
    setSelectedDate,
    selectedShowtime,
    showtimeDetail,
    loadingShowtime,
    selectedSeats,
    selectedCombos,
    customer,
    setCustomer,
    paymentMethod,
    setPaymentMethod,
    paymentConfig,
    submitting,
    pendingOrder,
    completedOrder,
    filteredMovies,
    availableDates,
    dateShowtimes,
    seatGroups,
    seatPrices,
    ticketTotal,
    comboTotal,
    grandTotal,
    handleSelectMovie,
    handleSelectShowtime,
    toggleSeatGroup,
    updateComboQty,
    handleCheckout,
    handleReset,
  };
}
