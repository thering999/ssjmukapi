<?php ob_start(); ?>

<div class="animate-fade-in">
    <header style="margin-bottom: 3rem; text-align: center;">
        <h1 style="font-size: 3rem; font-weight: 700; background: linear-gradient(to right, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1rem;">
            ระบบข้อมูลสุขภาพ
        </h1>
        <p style="color: var(--text-secondary); font-size: 1.25rem; max-width: 600px; margin: 0 auto;">
            สำนักงานสาธารณสุขจังหวัดมุกดาหาร
        </p>
    </header>

    <div class="dashboard-grid">
        <div class="card stat-card">
            <h3>สถานะระบบ</h3>
            <div class="value" id="system-status">Checking...</div>
        </div>
        
        <div class="card stat-card">
            <h3>ประชากรทั้งหมด</h3>
            <div class="value">350,000+</div>
            <div style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 0.5rem;">ข้อมูลล่าสุดปี 2568</div>
        </div>

        <div class="card stat-card">
            <h3>หน่วยบริการ</h3>
            <div class="value">120+</div>
            <div style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 0.5rem;">โรงพยาบาลและ รพ.สต.</div>
        </div>
    </div>

    <div style="margin-top: 4rem; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div class="card">
            <h2 style="margin-bottom: 1rem;">ประกาศล่าสุด</h2>
            <ul style="list-style: none;">
                <li style="padding: 1rem 0; border-bottom: 1px solid var(--card-border);">
                    <span style="color: var(--accent-color); font-size: 0.875rem;">28 พ.ย. 2568</span>
                    <div style="font-weight: 500;">แจ้งกำหนดการส่งรายงานประจำเดือน</div>
                </li>
                <li style="padding: 1rem 0; border-bottom: 1px solid var(--card-border);">
                    <span style="color: var(--accent-color); font-size: 0.875rem;">25 พ.ย. 2568</span>
                    <div style="font-weight: 500;">อัปเดตระบบ API เวอร์ชัน 1.2</div>
                </li>
            </ul>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 1rem;">บริการสำหรับนักพัฒนา</h2>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                เข้าถึงข้อมูลสาธารณะผ่าน API ที่ทันสมัยและปลอดภัย
            </p>
            <div style="display: flex; gap: 1rem;">
                <a href="/docs" class="btn btn-primary">ดูเอกสาร API</a>
                <a href="https://github.com/ssjmukdahan" target="_blank" class="btn" style="background: rgba(255,255,255,0.1);">GitHub</a>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/layouts/main.php'; 
?>
