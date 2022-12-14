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
                        <div class="item" v-for="item in nav.rightColumnNavItems">
                            <a :href="item.url">${ item.Title }</a>
                        </div>
                    </div>

                    <div class="column image" v-if="nav.featuredImage && nav.featuredImage.length > 0">
                        <a v-if="nav.featuredURL" :href="nav.featuredURL">
                            <img :src="getImagePath(nav.featuredImage[0].path)" />
                        </a>
                        <img v-else :src="getImagePath(nav.featuredImage[0].path)" />
                        <div class="text-center" v-if="nav.featuredText">
                            <a v-if="nav.featuredURL" :href="nav.featuredURL">${ nav.featuredText }</a>
                            <span v-else>${ nav.featuredText }</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </span>
</div>