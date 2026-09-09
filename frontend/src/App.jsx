import {
  BrowserRouter,
  Routes,
  Route,
  Navigate,
} from "react-router-dom";
import CustomerLayout from "./layouts/CustomerLayout";

import LoginCustomer from "./pages/LoginCustomer";
import HomeCustomer from "./pages/HomeCustomer";
import MovieShowtimes from "./pages/MovieShowtimes";
import SeatSelection from "./pages/SeatSelection";
import Payment from "./pages/Payment";
import ComboSelection from "./pages/ComboSelection";

import LoginInternal from "./pages/LoginInternal";
import Dashboard from "./pages/Dashboard";

// =========================
// BẢO VỆ DASHBOARD
// =========================
function InternalRoute({ children }) {
  const token = localStorage.getItem("token");
  const vaiTro = localStorage.getItem("vaiTro");

  const internalRoles = [
    "NHAN_VIEN",
    "QUAN_LY",
  ];

  if (
    !token ||
    !internalRoles.includes(vaiTro)
  ) {
    return (
      <Navigate
        to="/internal/login"
        replace
      />
    );
  }

  return children;
}

function CustomerRoute({ children }) {
  const token = localStorage.getItem("token");
  const vaiTro = localStorage.getItem("vaiTro");

  if (!token || vaiTro !== "KHACH_HANG") {
    return <Navigate to="/login" replace state={{ from: window.location.pathname }} />;
  }

  return children;
}

function App() {
  return (
    <BrowserRouter>
      <Routes>

        {/* =========================
            TRANG CHỦ CÔNG KHAI
        ========================= */}

        <Route
          path="/"
          element={<Navigate to="/home" replace />}
        />

        <Route
            path="/home"
            element={
              <CustomerLayout>
                  <HomeCustomer />
              </CustomerLayout>
            }
          />

        <Route
          path="/dat-ve/:maPhim"
          element={
            <CustomerRoute>
              <CustomerLayout>
                <MovieShowtimes />
              </CustomerLayout>
            </CustomerRoute>
          }
        />

        <Route
          path="/chon-ghe/:maLichChieu"
          element={
            <CustomerRoute>
              <CustomerLayout>
                <SeatSelection />
              </CustomerLayout>
            </CustomerRoute>
          }
        />

        <Route
          path="/thanh-toan/:maDonHang"
          element={
            <CustomerRoute>
              <CustomerLayout>
                <Payment />
              </CustomerLayout>
            </CustomerRoute>
          }
        />

        <Route
          path="/chon-combo/:maDonHang"
          element={
            <CustomerRoute>
              <CustomerLayout>
                <ComboSelection />
              </CustomerLayout>
            </CustomerRoute>
          }
        />

        {/* =========================
            ĐĂNG NHẬP / ĐĂNG KÝ
        ========================= */}

        <Route
          path="/login"
          element={
            <LoginCustomer initialTab="login" />
          }
        />

        <Route
          path="/register"
          element={
            <LoginCustomer initialTab="register" />
          }
        />

        {/* =========================
            NHÂN VIÊN / QUẢN LÝ
        ========================= */}

        <Route
          path="/internal/login"
          element={<LoginInternal />}
        />

        <Route
          path="/dashboard"
          element={
            <InternalRoute>
              <Dashboard />
            </InternalRoute>
          }
        />

      </Routes>
    </BrowserRouter>
  );
}

export default App;