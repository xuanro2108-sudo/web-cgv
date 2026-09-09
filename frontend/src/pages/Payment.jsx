import { useEffect } from "react";
import { useNavigate, useParams } from "react-router-dom";
import "./Payment.css";

function Payment() {
    const { maDonHang } = useParams();
    const navigate = useNavigate();

    useEffect(() => {
        navigate(`/chon-combo/${maDonHang}`, { replace: true });
    }, [maDonHang, navigate]);

    return <p className="payment-message">Đang chuyển đến thanh toán QR...</p>;
}

export default Payment;
