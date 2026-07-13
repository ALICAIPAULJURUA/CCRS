<?php
// Page header component - include this after <main> tag
?>
<div class="page-header-banner">
    <div class="container">
        <h1><?php echo $page_title ?? 'Community Crime Reporting System'; ?></h1>
        <p><?php echo $page_description ?? 'Safe, anonymous reporting for gender-based violence and child abuse'; ?></p>
    </div>
</div>

<style>
.page-header-banner {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
    padding: 3rem 0;
    margin-bottom: 2rem;
}

.page-header-banner h1 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.page-header-banner p {
    font-size: 1rem;
    opacity: 0.9;
    max-width: 600px;
}

@media (max-width: 768px) {
    .page-header-banner {
        padding: 2rem 0;
        text-align: center;
    }
    
    .page-header-banner h1 {
        font-size: 1.5rem;
    }
    
    .page-header-banner p {
        font-size: 0.9rem;
        margin: 0 auto;
    }
}
</style>