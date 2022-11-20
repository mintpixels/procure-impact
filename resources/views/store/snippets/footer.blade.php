<footer>

    <div class="footer-content">

        <div class="columns layout" v-if="data">

            <div v-for="column in data.navigation.column" class="column">

                <h5>${ column.headline }</h5>
                <ul>
                    <li v-for="link in column.repeater">
                        <a :href="link.url">${ link.title }</a>
                    </li>
                </ul>

            </div>

        </div>
    </div>

</footer>