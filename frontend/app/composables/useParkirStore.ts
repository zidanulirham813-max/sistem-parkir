export const useParkirStore = () => {
  const totalMember = useState('parkir_member', () => 0);
  const kendaraanMasuk = useState('parkir_masuk', () => 0);
  const kendaraanKeluar = useState('parkir_keluar', () => 0);
  const pendapatanHariIni = useState('parkir_pendapatan', () => 0);
  // breakdown: biar card bisa tampil Parkir vs Member
  const pendapatanParkirHariIni = useState('parkir_pendapatan_parkir', () => 0);
  const pendapatanMemberHariIni = useState('parkir_pendapatan_member', () => 0);
  const aktivitasList = useState<any[]>('parkir_aktivitas', () => []);

  const fetchDashboardData = async () => {
    try {
      const { $api } = useNuxtApp();
      const res: any = await $api.get('/dashboard/ringkasan');
      const data = res.data?.data || res.data;

      if (data) {
        totalMember.value = data.total_member || 0;
        kendaraanMasuk.value = data.kendaraan_masuk_hari_ini || 0;
        kendaraanKeluar.value = data.kendaraan_keluar_hari_ini || 0;
        pendapatanHariIni.value = data.pendapatan_hari_ini || 0;
        pendapatanParkirHariIni.value = data.pendapatan_parkir_hari_ini ?? data.pendapatan_hari_ini ?? 0;
        pendapatanMemberHariIni.value = data.pendapatan_member_hari_ini ?? 0;

        if (Array.isArray(data.aktivitas_terbaru) && data.aktivitas_terbaru.length > 0) {
          aktivitasList.value = data.aktivitas_terbaru.map((t: any) => ({
            text: t.plat_nomor ? `${t.kode_tiket} · ${t.plat_nomor}` : t.kode_tiket,
            time: t.waktu_keluar
              ? new Date(t.waktu_keluar).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
              : t.waktu_masuk
                ? new Date(t.waktu_masuk).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                : '-',
            type: t.status === 'Selesai' ? 'Keluar' : 'Masuk',
            plat: t.plat_nomor,
            status: t.status,
          }));
        } else if (aktivitasList.value.length === 0) {
          // fallback: kosong tapi jangan timpa jika sudah ada aktivitas simulasi lokal
        }
      }
    } catch (e) {
      console.error('Gagal memuat data dashboard dari server:', e);
    }
  };

  // Fetch trend 7 hari untuk diagram garis beranda
  const fetchTrend = async (days = 7) => {
    try {
      const { $api } = useNuxtApp();
      const res: any = await $api.get('/dashboard/trend', { params: { days } });
      const data = res.data?.data || res.data;
      if (Array.isArray(data)) return data;
      return null;
    } catch (e) {
      console.error('Gagal memuat trend dashboard:', e);
      return null;
    }
  };

  const tambahKendaraanMasuk = () => {
    kendaraanMasuk.value += 1;
    pendapatanHariIni.value += 5000;
    pendapatanParkirHariIni.value += 5000;
    aktivitasList.value.unshift({
      text: `B ${Math.floor(1000 + Math.random() * 9000)} ABC`,
      time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
      type: 'Masuk',
    });
  };

  const prosesKeluarParkir = () => {
    if (kendaraanMasuk.value > 0) {
      kendaraanMasuk.value -= 1;
      kendaraanKeluar.value += 1;
      aktivitasList.value.unshift({
        text: `D ${Math.floor(1000 + Math.random() * 9000)} XYZ`,
        time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
        type: 'Keluar',
      });
    }
  };

  return {
    totalMember,
    kendaraanMasuk,
    kendaraanKeluar,
    pendapatanHariIni,
    pendapatanParkirHariIni,
    pendapatanMemberHariIni,
    aktivitasList,
    fetchDashboardData,
    fetchTrend,
    tambahKendaraanMasuk,
    prosesKeluarParkir,
  };
};
