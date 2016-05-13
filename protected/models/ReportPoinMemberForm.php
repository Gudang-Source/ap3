<?php

/**
 * ReportPoinMemberForm class.
 * ReportPoinMemberForm is the data structure for keeping
 * report poin member form data. It is used by the 'poin member' action of 'ReportController'.
 */
class ReportPoinMemberForm extends CFormModel
{

    const SORT_BY_POIN_ASC = 1;
    const SORT_BY_POIN_DSC = 2;
    const SORT_BY_NAMA_ASC = 3;
    const SORT_BY_NAMA_DSC = 4;

    public $tahun;
    public $periodeId;
    public $jumlahDari;
    public $jumlahSampai;
    public $sortBy;

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return array(
            array('tahun, periodeId, sortBy', 'required', 'message' => '{attribute} tidak boleh kosong'),
            array('tahun, periodeId, sortBy, jumlahDari, jumlahSampai', 'numerical', 'integerOnly' => true),
            array('jumlahDari, jumlahSampai', 'safe')
        );
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels()
    {
        return array(
            'periodeId' => 'Periode',
            'sortBy' => 'Urutkan',
            'jumlahDari' => 'Jml Dari',
            'jumlahSampai' => 'Sampai'
        );
    }

    public function dataPeriode()
    {
        return Yii::app()->db->createCommand()
                        ->select('id, nama, awal, akhir')
                        ->from(MemberPeriodePoin::model()->tableName() . ' periode')
                        ->queryAll();
    }

    public function listPeriode()
    {
        $data = $this->dataPeriode();
        $list = [];
        foreach ($data as $periode) {
            $list[$periode['id']] = $periode['nama'] . ' (' . $this->namaBulan($periode['awal']) . ' - ' . $this->namaBulan($periode['akhir']) . ')';
        }
        return $list;
    }

    public function namaBulan($i)
    {
        static $bulan = array(
            "Januari",
            "Februari",
            "Maret",
            "April",
            "Mei",
            "Juni",
            "Juli",
            "Agustus",
            "September",
            "Oktober",
            "November",
            "Desember"
        );
        return $bulan[$i - 1];
    }

    public function listSortBy()
    {
        return [
            self::SORT_BY_POIN_DSC => 'Jumlah Poin (z-a)',
            self::SORT_BY_POIN_ASC => 'Jumlah Poin (a-z)',
        ];
    }

    public function ambilDataPoinMember()
    {
        $periode = MemberPeriodePoin::model()->findByPk($this->periodeId);
        $command = Yii::app()->db->createCommand()
                ->select('profil_id, p.nomor, p.nama, sum(poin) poin, p.alamat1, p.alamat2, p.alamat3, p.telp, p.hp, p.surel, p.identitas, p.tanggal_lahir')
                ->from(PenjualanMember::model()->tableName() . ' t')
                ->join(Profil::model()->tableName() . ' p', 't.profil_id = p.id')
                ->where('year(t.updated_at)=:tahun and month(t.updated_at) between :awal and :akhir')
                ->group('profil_id');

        if ($this->sortBy == self::SORT_BY_POIN_ASC) {
            $command->order('sum(poin), p.nama');
        } elseif ($this->sortBy == self::SORT_BY_POIN_DSC) {
            $command->order('sum(poin) desc, p.nama');
        }

        $command->bindValues([':tahun' => $this->tahun, ':awal' => $periode->awal, ':akhir' => $periode->akhir]);
        return $command->queryAll();
    }

}
