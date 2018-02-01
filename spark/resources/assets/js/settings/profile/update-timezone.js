module.exports = {
    props: ['user'],

    /**
     * The component's data.
     */
    data() {
        return {
            form: $.extend(true, new SparkForm({
                timezone: ''
            }), Spark.forms.updateTimezone)
        };
    },


    /**
     * Bootstrap the component.
     */
    mounted() {
        this.form.timezone = this.user.timezone;
    },


    methods: {
        /**
         * Update the user's contact information.
         */
        update() {
            Spark.put('/settings/timezone', this.form)
                .then(() => {
                    Bus.$emit('updateUser');
                });
        }
    }
};
