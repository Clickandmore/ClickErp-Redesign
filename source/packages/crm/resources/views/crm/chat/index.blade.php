@extends('click.layout.indexs.index')

@section('style')
    <link rel="stylesheet" href="{{ asset('admin') }}/assets/cssbundle/dataTables.min.css">
    <style>
        :root {
            --body-bg: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            --msger-bg: #fff;
            --border: 2px solid #ddd;
            --left-msg-bg: #ececec;
            --right-msg-bg: #28a745;
        }

        .msger {
            display: flex;
            flex-flow: column wrap;
            justify-content: space-between;
            width: 100%;
            margin: 25px 10px;
            height: calc(100% - 50px);
            min-height: 600px;
            border: var(--border);
            border-radius: 5px;
            background: var(--msger-bg);
            box-shadow: 0 15px 15px -5px rgba(0, 0, 0, 0.2);
        }

        .msger-header {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: var(--border);
            background: #eee;
            color: #666;
        }

        .msger-chat {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .msger-chat::-webkit-scrollbar {
            width: 6px;
        }

        .msger-chat::-webkit-scrollbar-track {
            background: #ddd;
        }

        .msger-chat::-webkit-scrollbar-thumb {
            background: #bdbdbd;
        }

        .msg {
            display: flex;
            align-items: flex-end;
            margin-bottom: 10px;
        }

        .msg:last-of-type {
            margin: 0;
        }

        .msg-img {
            width: 50px;
            height: 50px;
            margin-right: 10px;
            background: #ddd;
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            border-radius: 50%;
        }

        .msg-bubble {
            max-width: 450px;
            padding: 15px;
            border-radius: 15px;
            background: var(--left-msg-bg);
        }

        .msg-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .msg-info-name {
            margin-right: 10px;
            font-weight: bold;
        }

        .msg-info-time {
            font-size: 0.85em;
        }

        .left-msg .msg-bubble {
            border-bottom-left-radius: 0;
        }

        .right-msg {
            flex-direction: row-reverse;
        }

        .right-msg .msg-bubble {
            background: var(--right-msg-bg);
            color: #fff;
            border-bottom-right-radius: 0;
        }

        .right-msg .msg-img {
            margin: 0 0 0 10px;
        }

        .msger-inputarea {
            display: flex;
            padding: 10px;
            border-top: var(--border);
            background: #eee;
            align-items: center;
            gap: 4px;
            position: relative;
        }

        .msger-inputarea * {
            border: none;
            border-radius: 4px;
            font-size: 1em;
        }

        .msger-input {
            flex: 1;
            background: #ddd;
            min-height: 48px;
            padding: 8px;
        }

        .msger-send-btn {
            background: rgb(0, 196, 65);
            color: #fff;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.23s;
            min-height: 48px;
            padding: 8px 24px;
            border-radius: 4px;
        }

        .msger-send-btn:hover {
            background: rgb(0, 180, 50);
        }

        .msger-chat {
            background-color: #fcfcfe;
            background-image: url("https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png");
        }

        .file-upload-wrapper {
            padding: 0;
            /*position: relative;*/
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .file-upload-wrapper label {
            display: flex;
            align-items: center;
            background: rgb(145, 145, 145);
            color: #fff;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.23s;
            margin: 0;
            width: 48px ;
            aspect-ratio: 1;
            justify-content: center;
            gap: 4px;
        }

        .file-upload-wrapper label:hover {
            background: rgb(0, 180, 50);
        }

        .file-upload-wrapper input[type="file"] {
          display: none;
        }

        .file-preview {
            font-size: 0.9em;
            color: #333;
            position: absolute;
            bottom: 60px;
            left: 10px;
        }

        .file-preview img {
            max-width: 100px;
            max-height: 100px;
            display: block;
        }

        .file-preview a {
            color: #007bff;
            text-decoration: none;
        }

        .file-preview a:hover {
            text-decoration: underline;
        }

    </style>
@endsection

@section('page-title')
    {{ $bladeTitle }}
@endsection

@section('page-links')
    <li class="breadcrumb-item active" aria-current="page"> {{ $bladeTitle }}</li>
@endsection

@section('content')
    <section class="msger">
        <header class="msger-header">
            <div class="msger-header-title">
                <i class="fas fa-comment-alt"></i> {{$ticket->notes}}
            </div>
            <div class="msger-header-options">
                <span><i class="fas fa-cog"></i></span>
            </div>
        </header>

        <main class="msger-chat">
            @foreach($messages as $message)
                @if($message->from_user_id != $ticket->user_id)
                    <div class="msg left-msg">
                        <div
                                class="msg-img"
                                style="background-image: url('{{ asset('assets/images/default-arab2.avif') }}')">
                        </div>

                        <div class="msg-bubble">
                            <div class="msg-info">
                                <div class="msg-info-name mr-3">{{ $message->user->name }}</div>
                                <div class="msg-info-time">{{ $message->created_at->format('Y-m-d H:i A') }}</div>
                            </div>

                            <div class="msg-text">
                                {{ $message->note }}
                            </div>

                            @if($message->file)
                                <div class="msg-file">
                                    <a href="{{ showFile($message->file) }}" target="_blank"
                                       style="color: #303030!important;font-weight: bold!important;">{{__(trans('crm.View Attached File'))}}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="msg right-msg">
                        <div class="msg-bubble">
                            <div class="msg-info">
                                <div class="msg-info-name mr-3">{{ $message->user->name }}</div>
                                <div class="msg-info-time">{{ $message->created_at->format('Y-m-d H:i A') }}</div>
                            </div>

                            <div class="msg-text">
                                {{ $message->note }}
                            </div>

                            @if($message->file)
                                <div class="msg-file">
                                    <a href="{{ showFile($message->file) }}" target="_blank"
                                       style="color: #303030!important;font-weight: bold!important;">{{__(trans('crm.View Attached File'))}}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </main>
        @if($ticket->status != 'ended')
            <form id="msgerForm" class="msger-inputarea" enctype="multipart/form-data">
                <input type="hidden" value="{{$ticket->id}}" name="ticket_id">
                <input type="text" name="message" class="msger-input" placeholder="{{__('crm.Enter your message')}}">
                <div class="file-upload-wrapper">
                    <input type="file" name="file" class="msger-file-input" id="fileInput">
                    <label for="fileInput">
                        <i class="fas fa-paperclip"></i>
                    </label>
                    <div id="filePreview" class="file-preview"></div>
                </div>

                <button type="submit" class="msger-send-btn">{{__('crm.Send')}}</button>
            </form>
        @else
            <div class="text-center p-3">
                <h3>
                    تم اغلاق التذكرة
                    <i class="fa fa-check-circle text-success"></i>
                </h3>
            </div>
        @endif

    </section>
@endsection

@section('js')
    <script>
        const msgerForm = document.getElementById("msgerForm");
        const msgerInput = get(".msger-input");
        const msgerFileInput = get(".msger-file-input");
        const msgerChat = get(".msger-chat");

        const PERSON_IMG = "";
        const PERSON_NAME = "{{auth()->user()->name}}";

        msgerForm.addEventListener("submit", event => {
            event.preventDefault();

            const msgText = msgerInput.value;
            const formData = new FormData(msgerForm);
            if (!msgText && !msgerFileInput.files.length) return;
            const side = "{{ auth()->user()->user_type == 'S' ? 'left' : 'right' }}";

            $.ajax({
                url: "{{ route('crm.chat.store') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    appendMessage(PERSON_NAME, PERSON_IMG, side, msgText);
                    msgerInput.value = "";
                    msgerFileInput.value = "";
                    $('#filePreview').hide();
                },
                error: function (error) {
                    console.error("Error sending message:", error);
                }
            });
        });

        // <div class="msg-img" style="background-image: url(${img})"></div>
        function appendMessage(name, img, side, text, fileUrl = null) {
            const fileHtml = fileUrl ? `<div class="msg-file"><a href="${fileUrl}" target="_blank">View Attached File</a></div>` : '';
            const msgHTML = `
        <div class="msg ${side}-msg">

            <div class="msg-bubble">
                <div class="msg-info">
                    <div class="msg-info-name">${name}</div>
                    <div class="msg-info-time">${formatDate(new Date())}</div>
                </div>
                <div class="msg-text">${text}</div>
                ${fileHtml}
            </div>
        </div>
    `;

            msgerChat.insertAdjacentHTML("beforeend", msgHTML);
            msgerChat.scrollTop += 500;
        }

        // Utils
        function get(selector, root = document) {
            return root.querySelector(selector);
        }

        function formatDate(date) {
            const h = "0" + date.getHours();
            const m = "0" + date.getMinutes();
            return `${h.slice(-2)}:${m.slice(-2)}`;
        }

        document.getElementById('fileInput').addEventListener('change', function (event) {
            const fileInput = event.target;
            const file = fileInput.files[0];
            const filePreview = document.getElementById('filePreview');

            if (file) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    if (file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        filePreview.appendChild(img);
                    } else {
                        const fileLink = document.createElement('a');
                        fileLink.href = URL.createObjectURL(file);
                        fileLink.textContent = file.name;
                        fileLink.download = file.name;
                        filePreview.appendChild(fileLink);
                    }
                };

                reader.readAsDataURL(file);
            } else {
                filePreview.innerHTML = '';
            }
        });
    </script>
@endsection
