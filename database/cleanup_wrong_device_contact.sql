-- =====================================================================
-- Bersihkan kontak yang TERLANJUR dibuat dengan nomor DEVICE sendiri
-- (bug: webhook Wablas mengambil `sender` = nomor device, bukan `phone`).
--
-- Nomor device Wablas (1NE969): 6281235197654
-- Jalankan di DB production. URUTAN: PREVIEW dulu, baru DELETE.
-- =====================================================================

SET @device_phone = '6281235197654';

-- ---------------------------------------------------------------------
-- 1) PREVIEW — pastikan ini benar nomor device sendiri, BUKAN lead asli.
--    Lihat kontak, jumlah percakapan & pesan yang akan ikut terhapus.
-- ---------------------------------------------------------------------
SELECT 'contact' AS item, id, name, phone, channel, created_at
FROM contacts
WHERE phone = @device_phone;

SELECT 'conversations' AS item, COUNT(*) AS jumlah
FROM conversations
WHERE contact_id IN (SELECT id FROM contacts WHERE phone = @device_phone);

SELECT 'messages' AS item, COUNT(*) AS jumlah
FROM messages
WHERE conversation_id IN (
    SELECT id FROM conversations
    WHERE contact_id IN (SELECT id FROM contacts WHERE phone = @device_phone)
);

SELECT 'lead_scores' AS item, COUNT(*) AS jumlah
FROM lead_scores
WHERE conversation_id IN (
    SELECT id FROM conversations
    WHERE contact_id IN (SELECT id FROM contacts WHERE phone = @device_phone)
);

-- ---------------------------------------------------------------------
-- 2) DELETE — hapus berjenjang dalam satu transaksi.
--    Hapus baris -- di bawah ini (uncomment) HANYA setelah preview benar.
--    Kalau hasil preview salah, jalankan ROLLBACK; bukan COMMIT;
-- ---------------------------------------------------------------------
-- START TRANSACTION;
--
-- DELETE FROM lead_scores
-- WHERE conversation_id IN (
--     SELECT id FROM conversations
--     WHERE contact_id IN (SELECT id FROM contacts WHERE phone = @device_phone)
-- );
--
-- DELETE FROM messages
-- WHERE conversation_id IN (
--     SELECT id FROM conversations
--     WHERE contact_id IN (SELECT id FROM contacts WHERE phone = @device_phone)
-- );
--
-- DELETE FROM conversations
-- WHERE contact_id IN (SELECT id FROM contacts WHERE phone = @device_phone);
--
-- DELETE FROM contacts WHERE phone = @device_phone;
--
-- COMMIT;
-- ---------------------------------------------------------------------
