<?php

namespace App\Http\Controllers;

use App\Models\PenjualanRetail;
use App\Models\PermintaanSupplier;
use App\Models\StokRetail;
use App\Models\StokSupplier;
use Illuminate\Http\Request;

class RetailController extends Controller
{
    public function dashboard()
    {
        $jumlahBarangs = StokRetail::count();
        $penjualanBarangs = PenjualanRetail::count();

        $stokBarangs = 0;
        foreach(StokRetail::all() as $stokRetail){
            $stokBarangs = $stokBarangs + $stokRetail->stok;
        }

        $totalBarangs = 0;
        foreach(PenjualanRetail::all() as $penjualanRetail){
            $totalBarangs = $totalBarangs + $penjualanRetail->total;
        }

        return view('retail.dashboard', compact('jumlahBarangs', 'penjualanBarangs', 'stokBarangs', 'totalBarangs'));
    }

    public function pasokan()
    {
        $stokSuppliers = StokSupplier::all();
        
        return view('retail.pasokan', compact('stokSuppliers'));
    }

    public function formPesanBarang($id)
    {
        $stokSuppliers = StokSupplier::where('id_barang', $id)->get();
        
        return view('retail/form-pesan', compact('stokSuppliers'));
    }

    public function tambahPesanan(Request $request)
    {
        foreach(StokSupplier::where('id_barang', $request->id_barang)->get() as $stokSupplier){
            if($request->total <= $stokSupplier->stok){
                PermintaanSupplier::create([
                    'id_barang' => $request->id_barang,
                    'id_retail' => 1,
                    'total' => $request->total
                ]);
            }  
        }
        return redirect('retail/pesan');
    }

    public function pesan()
    {
        $permintaanSuppliers = PermintaanSupplier::leftJoin('stok_suppliers', 'permintaan_suppliers.id_barang', '=', 'stok_suppliers.id_barang')->get();
        
        return view('retail.pesan', compact('permintaanSuppliers'));
    }

    public function batalPesanan($id)
    {
        PermintaanSupplier::where('id_pesanan', $id)
                        ->update(['status' => 'Batal']);
        
        return redirect('retail/pesan');
    }

    public function formTerimaPesanan($id)
    {
        $permintaanSuppliers = PermintaanSupplier::leftJoin('stok_suppliers', 'permintaan_suppliers.id_barang', '=', 'stok_suppliers.id_barang')
                                                    ->where('id_pesanan', $id)
                                                    ->get();
                
        return view('retail.terima-pesanan', compact('permintaanSuppliers'));
    }

    public function terimaPesanan(Request $request)
    {        
        PermintaanSupplier::where('id_pesanan', $request->id_pesanan)
                            ->update(['status' => 'Selesai']);

        StokRetail::create([
            'nama_barang' => $request->nama_barang,
            'stok' => $request->total
        ]);
        
        return redirect('retail/pesan');
    }

    public function detailPesanan($id)
    {
        $permintaanSuppliers = PermintaanSupplier::leftJoin('stok_suppliers', 'permintaan_suppliers.id_barang', '=', 'stok_suppliers.id_barang')
                                                    ->where('id_pesanan', $id)
                                                    ->get();
        
        return view('retail.detail-pesanan', compact('permintaanSuppliers'));
    }

    public function stok()
    {
        $stokRetails = StokRetail::all();
        
        return view('retail.stok', compact('stokRetails'));
    }

    public function formEditBarang($id)
    {
        $stokRetails = StokRetail::where('id_barang', $id)->get();
        
        return view('retail.form-edit', compact('stokRetails'));
    }

    public function editBarang(Request $request)
    {  
        StokRetail::where('id_barang', $request->id_barang)
                    ->update(['nama_barang' => $request->nama_barang, 'stok' => $request->stok, 'keterangan' => 'Tersedia']);
        
        return redirect('retail/stok');
    }

    public function hapusBarang($id)
    {
        StokRetail::where('id_barang', $id)->delete();
        
        return redirect('retail/stok');
    }

    public function formTambahPenjualan($id)
    {
        $stokRetails = stokRetail::where('id_barang', $id)->get();
        
        return view('retail.form-jual', compact('stokRetails'));
    }

    public function tambahPenjualan(Request $request)
    {
        foreach(StokRetail::where('id_barang', $request->id_barang)->get() as $stokRetail){
            if($request->total < $stokRetail->stok){
                StokRetail::where('id_barang', $request->id_barang)
                            ->update(['stok' => ($stokRetail->stok - $request->total)]);
                PenjualanRetail::create([
                    'id_barang' => $request->id_barang,
                    'id_retail' => 1,
                    'total' => $request->total
                ]);
            } else if ($request->total == $stokRetail->stok){
                StokRetail::where('id_barang', $request->id_barang)
                            ->update(['stok' => ($stokRetail->stok - $request->total), 'keterangan' => 'Habis']);
                PenjualanRetail::create([
                    'id_barang' => $request->id_barang,
                    'id_retail' => 1,
                    'total' => $request->total
                ]);
            }
        }

        return redirect('retail/penjualan');
    }

    public function penjualan()
    {
        $penjualanRetails = PenjualanRetail::leftJoin('stok_retails', 'penjualan_retails.id_barang', '=', 'stok_retails.id_barang')->get();
        
        return view('retail.penjualan', compact('penjualanRetails'));
    }

    public function detailPenjualan($id)
    {
        $penjualanRetails = PenjualanRetail::leftJoin('stok_retails', 'penjualan_retails.id_barang', '=', 'stok_retails.id_barang')
                                            ->where('id_penjualan', $id)
                                            ->get();
        
        return view('retail.detail-penjualan', compact('penjualanRetails'));
    }
}
