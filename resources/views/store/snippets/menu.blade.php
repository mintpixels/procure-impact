<div id="menu-bar" class="menu-bar">
    <span v-for="nav in data.Navigation">
        <a :href="nav.URL">${ nav.Title }</a>

        <div v-if="nav.Dropdown" class="subnav">
            <div class="subnav-contents">
                <div class="columns">
                    <div class="column left">
                        <div v-for="item in nav.leftColumnNavItems">
                            <a :href="item.url">${ item.Title }</a>
                        </div>
                    </div>

                    <div class="column right">
                        <div v-for="item in nav.rightColumnNavItems">
                            <a :href="item.url">${ item.Title }</a>
                        </div>
                    </div>

                    <div class="column image" v-if="nav.featuredImage">
                        <img :src="getImagePath(nav.featuredImage[0].path)" />
                    </div>
                </div>
            </div>
        </div>
    </span>
</div>