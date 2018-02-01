<spark-profile :user="user" inline-template>
    <div>
        <!-- Update Profile Photo -->
        @include('spark::settings.profile.update-profile-photo')

        <!-- Update Contact Information -->
        @include('spark::settings.profile.update-contact-information')

        <!-- Update Timezone -->
        @include('spark::settings.profile.update-timezone')
    </div>
</spark-profile>
