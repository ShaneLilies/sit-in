<?php
$pageTitle = 'Testimonials';
require_once 'auth.php';

// Mark ALL unread feedback as read now that admin is viewing
$pdo->exec("UPDATE feedback SET is_read = 1 WHERE is_read = 0");

$feedbacks = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC")->fetchAll();
include 'header.php';
?>
<div class="page-content">
    <div class="page-title">Student Testimonials</div>
    <div class="table-card">
        <h3><i class="fas fa-star"></i> Student Testimonials</h3>
        <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Note: Testimonials with a rating of 4 or 5 stars are automatically featured on the Student Dashboard.</p>
        <table id="feedbackTable" class="dataTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ID Number</th>
                    <th>Student Name</th>
                    <th>Rating</th>
                    <th>Message</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($feedbacks as $f): ?>
            <tr>
                <td><?= $f['id'] ?></td>
                <td><?= htmlspecialchars($f['id_number']) ?></td>
                <td><?= htmlspecialchars($f['student_name']) ?></td>
                <td>
                    <?php
                    $rating = (int)($f['rating'] ?? 0);
                    echo '<span style="color:#F0B429;font-size:15px">'
                        . str_repeat('★', $rating)
                        . str_repeat('☆', 5 - $rating)
                        . '</span> (' . $rating . '/5)';
                    ?>
                </td>
                <td><?= htmlspecialchars($f['message']) ?></td>
                <td><?= htmlspecialchars($f['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>$(document).ready(function(){ $('#feedbackTable').DataTable({ order: [[5,'desc']] }); });</script>
</body></html>