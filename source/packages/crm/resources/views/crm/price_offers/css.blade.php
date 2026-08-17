<style>
    * {
        box-sizing: border-box;
        padding: 0;
        margin: 0;
        direction: rtl;
        text-align: right;
    }

    p {
        margin: 0;
        padding: 0;
        line-height: 1.5;
    }

    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    section.paper {
        height: 270mm;
        width: 210mm;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        page-break-after: always;
    }

    .main {
        flex-grow: 1;
        padding: 30px;
        overflow: hidden;
    }
    .Logo {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .Logo img {
        width: 100px;
    }

    svg {
        width: 150px;
    }

    .text_logo hr {
        padding: 0;
        margin: 2px 0px;
        color: gold;
    }

    .info p {
        font-size: 1rem;
        font-weight: 500;
        margin-top: 8px;
    }

    .title {
        font-size: 1.5rem;
        font-weight: 600;
        text-decoration: underline;
        text-align: center;
    }

    .title_line {
        margin: 0 auto;
        width: 450px;
        border-top: 5px solid black;
    }

    .content {
        font-size: 1rem;
        text-align: center;
        margin-top: 12px;
    }

    .about {
        text-align: start;
    }

    .line {
        width: 320px;
        border-top: 5px solid black;
    }

    .contact {
        display: flex;
        align-items: center;
        gap: 24px;
        text-align: center;
        margin: auto;
        margin-bottom: 0;
        padding: 16px;
    }

    .contact .contactType {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .contact .contactType p {
        font-size: 12px;
    }

    .contact .contactType .icon {
        background-color: #d7ab38;
        border-radius: 100px;
        padding: 4px;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .contact .contactType .icon svg {
        width: 20px;
        aspect-ratio: 1;
    }

    header {
        padding: 16px;
    }
    header svg{
        margin-left: 65px;
    }

    footer {
        display: flex;
        align-items: center;
        justify-content: space-evenly;
        background-color: #eee;
        padding: 16px;
    }

    .adress {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .adress svg {
        width: 30px;
        border-radius: 50%;
        background-color: #d7ab38;
        color: #fff !important;
        border: 1px solid #d7ab38;
        padding: 4px;
    }

    .adress .words p {
        font-size: 1rem;
    }

    table,
    th,
    td {
        border: 1px solid black;
        border-collapse: collapse;
        padding: 2px;
    }

    table tr th {
        background-color: rgb(183, 180, 180);
    }

    .one {
        font-size: 1.4rem;
        font-weight: 500;
        border-bottom: 2px solid black;
        text-align: center;
    }

    .two {
        font-size: 1.5rem;
        font-weight: 500;
        text-align: center;
    }
</style>
