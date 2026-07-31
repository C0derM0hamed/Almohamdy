<section class="el-page-hero" aria-labelledby="elPageTitle">
    <div>
        <h1 id="elPageTitle">{{ $title }}</h1>
        @if (! empty($subtitle))
            <p>{{ $subtitle }}</p>
        @endif
    </div>
    <div class="el-page-hero-art" aria-hidden="true"></div>
</section>
