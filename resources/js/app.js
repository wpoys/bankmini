(function () {
    "use strict";

    function doc(sel) {
        return document.querySelector(sel);
    }
    function all(sel) {
        return Array.prototype.slice.call(document.querySelectorAll(sel));
    }

    function onReady(fn) {
        if (document.readyState !== "loading") {
            fn();
        } else {
            document.addEventListener("DOMContentLoaded", fn);
        }
    }

    /* ---------- Debounce helper ---------- */
    function debounce(fn, wait) {
        let t;
        return function () {
            const ctx = this,
                args = arguments;
            clearTimeout(t);
            t = setTimeout(function () {
                fn.apply(ctx, args);
            }, wait);
        };
    }

    /* ---------- TOAST ---------- */
    function showToast(message, type) {
        type = type || "success";
        const iconMap = {
            success: "fa-circle-check",
            danger: "fa-circle-exclamation",
            warning: "fa-triangle-exclamation",
            info: "fa-circle-info",
        };
        let container = doc(".toast-container:not(.notify-holder)");
        if (!container) {
            container = document.createElement("div");
            container.className = "toast-container";
            document.body.appendChild(container);
        }
        const toast = document.createElement("div");
        toast.className = "toast-notif " + type;
        toast.setAttribute("role", "alert");
        const icon = iconMap[type] || iconMap.info;
        toast.innerHTML =
            '<i class="fa-solid ' +
            icon +
            '"></i><span>' +
            (message || "").replace(/</g, "&lt;") +
            "</span>";
        container.appendChild(toast);
        setTimeout(function () {
            toast.style.transition = "opacity 0.4s ease, transform 0.4s ease";
            toast.style.opacity = "0";
            toast.style.transform = "translateX(16px)";
            setTimeout(function () {
                toast.remove();
            }, 420);
        }, 3600);
    }

    onReady(function () {
        /* Auto-dismiss flash toasts */
        all(".toast-notif").forEach(function (toast) {
            setTimeout(function () {
                toast.style.transition =
                    "opacity 0.4s ease, transform 0.4s ease";
                toast.style.opacity = "0";
                toast.style.transform = "translateX(16px)";
                setTimeout(function () {
                    toast.remove();
                }, 420);
            }, 3600);
        });
        all(".notify-holder").forEach(function (holder) {
            setTimeout(function () {
                holder.remove();
            }, 4200);
        });
    });

    /* ---------- Sidebar (mobile drawer) ---------- */
    onReady(function () {
        const sidebar = doc("#appSidebar");
        const toggle = doc("#sidebarToggle");
        const backdrop = doc("#sidebarBackdrop");

        const closeSidebar = function () {
            if (sidebar) sidebar.classList.remove("active");
            if (backdrop) backdrop.classList.remove("show");
        };

        if (toggle && sidebar) {
            toggle.addEventListener("click", function () {
                sidebar.classList.toggle("active");
                if (backdrop) backdrop.classList.toggle("show");
            });
        }
        if (backdrop) backdrop.addEventListener("click", closeSidebar);
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") closeSidebar();
        });
    });

    /* ---------- Sidebar minimize / expand (desktop, tersimpan di localStorage) ---------- */
    onReady(function () {
        const collapseBtn = doc("#sidebarCollapse");
        const MINI_KEY = "bankmini.sidebar.mini";

        const isMini = function () {
            return document.documentElement.classList.contains("sidebar-mini");
        };

        const setMini = function (mini, persist) {
            document.documentElement.classList.toggle("sidebar-mini", mini);
            if (collapseBtn) {
                collapseBtn.setAttribute("aria-expanded", String(!mini));
                collapseBtn.setAttribute("aria-label", mini ? "Perluas sidebar" : "Minimalkan sidebar");
                collapseBtn.setAttribute("title", mini ? "Perluas menu" : "Minimalkan menu");
            }
            if (persist) {
                try {
                    localStorage.setItem(MINI_KEY, mini ? "1" : "0");
                } catch (e) {
                    /* storage tidak tersedia */
                }
            }
        };

        setMini(isMini(), false);

        if (collapseBtn) {
            collapseBtn.addEventListener("click", function () {
                setMini(!isMini(), true);
            });
        }

        all("#appSidebar .nav-dropdown-toggle").forEach(function (btn) {
            btn.addEventListener("click", function () {
                if (isMini()) setMini(false, true);
            });
        });
    });

    /* ---------- Fit teks nominal kartu statistik (agar selalu muat satu baris) ---------- */
    onReady(function () {
        const fitStatValues = function () {
            all(".stat-card .stat-value").forEach(function (el) {
                if (el.classList.contains("fs-6")) return;
                el.style.fontSize = "";
                const minPx = 10;
                let size = parseFloat(window.getComputedStyle(el).fontSize) || 16;
                let guard = 0;
                while (
                    el.scrollWidth > Math.ceil(el.clientWidth) &&
                    size > minPx &&
                    guard++ < 25
                ) {
                    size = Math.max(minPx, size - 0.5);
                    el.style.fontSize = size + "px";
                }
            });
        };
        fitStatValues();
        window.addEventListener("resize", debounce(fitStatValues, 200));
        window.addEventListener("load", fitStatValues);
    });

    /* ---------- Tombol lihat/sembunyikan password ---------- */
    onReady(function () {
        all('input[type="password"]').forEach(function (input) {
            if (input.dataset.pwToggle) return;
            input.dataset.pwToggle = "1";

            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "pw-toggle";
            btn.title = "Lihat password";
            btn.setAttribute("aria-label", "Lihat password");
            btn.innerHTML = '<i class="fa-solid fa-eye" aria-hidden="true"></i>';
            const icons = btn.querySelector("i");

            btn.addEventListener("click", function () {
                const show = input.type === "password";
                input.type = show ? "text" : "password";
                icons.className = "fa-solid " + (show ? "fa-eye-slash" : "fa-eye");
                btn.title = show ? "Sembunyikan password" : "Lihat password";
                btn.setAttribute("aria-label", btn.title);
                input.focus();
            });

            if (
                input.parentElement &&
                input.parentElement.classList.contains("input-group")
            ) {
                input.parentElement.appendChild(btn);
            } else {
                const wrap = document.createElement("div");
                wrap.className = "input-group pw-wrap";
                input.parentNode.insertBefore(wrap, input);
                wrap.appendChild(input);
                wrap.appendChild(btn);
            }
        });
    });

    /* ---------- Loading state + double-submit prevention ---------- */
    onReady(function () {
        all(".btn-submit-loading").forEach(function (btn) {
            btn.closest("form").addEventListener("submit", function () {
                btn.disabled = true;
                btn.dataset.originalHtml = btn.innerHTML;
                btn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Memproses...';
            });
        });

        document.addEventListener("submit", function (e) {
            const form = e.target;
            if (!form || form.tagName !== "FORM") return;
            const btn = form.querySelector(
                'button[type="submit"].js-confirm-proses',
            );
            if (!btn) return;
            if (btn.disabled) {
                e.preventDefault();
                return;
            }
        });

        all("form[data-wait-submit]").forEach(function (form) {
            let sent = false;
            form.addEventListener("submit", function () {
                if (sent) {
                    return;
                }
                sent = true;
                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.dataset.originalHtml = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memproses...';
                }
            });
        });
    });

    /* ---------- Nominal format + saldo estimation (transaction page) ---------- */
    onReady(function () {
        const form = doc("#formTransaksi");
        if (!form) return;

        const jenis = doc("#jenisTrans");
        const nominal = doc("#nominal");
        const saldoSebelum = doc("#saldoSebelum");
        const teksSebelum = doc("#teksSaldoSebelum");
        const teksSesudah = doc("#teksSaldoSesudah");
        const blokKonfirmasiBesar = doc("#blokKonfirmasiBesar");

        const BATAS_BESAR =
            window.batasPenarikanBesar !== undefined
                ? window.batasPenarikanBesar
                : 5000000;

        function fmt(v) {
            return "Rp " + Number(v || 0).toLocaleString("id-ID");
        }

        function hitung() {
            const j = jenis ? jenis.value : "setoran";
            const n = parseInt((nominal && nominal.value) || "0", 10) || 0;
            const sebelum =
                parseInt((saldoSebelum && saldoSebelum.value) || "0", 10) || 0;
            const tipe = j === "setoran" ? 1 : -1;
            const sesudah = Math.max(0, sebelum + tipe * n);
            if (teksSebelum) teksSebelum.textContent = fmt(sebelum);
            if (teksSesudah) teksSesudah.textContent = fmt(sesudah);

            if (blokKonfirmasiBesar) {
                const perlu = j === "penarikan" && n > BATAS_BESAR;
                blokKonfirmasiBesar.style.display = perlu ? "block" : "none";
            }
        }

        if (nominal) nominal.addEventListener("input", hitung);
        if (jenis) jenis.addEventListener("change", hitung);
        hitung();
    });

    /* ---------- Segmented control (jenis transaksi) ---------- */
    onReady(function () {
        all("[data-jenis-target]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                const target = btn.getAttribute("data-jenis-target");
                const val = btn.getAttribute("data-value");
                if (target === "jenisTrans") {
                    const hid = doc("#jenisTrans");
                    if (hid) hid.value = val;
                }
                btn.parentElement
                    .querySelectorAll(".seg-btn")
                    .forEach(function (b) {
                        b.classList.remove("active");
                    });
                btn.classList.add("active");
                const ev = new Event("change");
                const hid = doc("#jenisTrans");
                if (hid) hid.dispatchEvent(ev);
            });
        });

        all("[data-shortcut]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                const target = doc(
                    btn.getAttribute("data-shortcut-target") || "#nominal",
                );
                if (target) {
                    target.value = btn.getAttribute("data-shortcut");
                    target.dispatchEvent(new Event("input", { bubbles: true }));
                    target.focus();
                }
            });
        });
    });

    /* ---------- Confirm modal helper (data-confirm attributes) ---------- */
    onReady(function () {
        const confirmModal = doc("#modalKonfirmasi");
        if (!confirmModal) return;

        let pendingSubmit = null;

        document.addEventListener("click", function (e) {
            const trigger = e.target.closest(
                "[data-confirm], form[data-confirm]",
            );
            if (!trigger) return;

            const message =
                trigger.getAttribute("data-confirm") ||
                "Apakah Anda yakin ingin melanjutkan tindakan ini?";
            const title =
                trigger.getAttribute("data-confirm-title") ||
                "Konfirmasi Tindakan";

            doc("#konfirmasiJudul").textContent = title;
            doc("#konfirmasiPesan").innerHTML = (message || "").replace(
                /\\n|\n/g,
                "<br>",
            );

            if (trigger.tagName === "FORM") {
                e.preventDefault();
                pendingSubmit = trigger;
                const modal = bootstrap.Modal.getOrCreateInstance(confirmModal);
                modal.show();
                return;
            } else if (trigger.tagName === "BUTTON" && trigger.form) {
                e.preventDefault();
                pendingSubmit = function () {
                    trigger.form.submit();
                };
                const modal = bootstrap.Modal.getOrCreateInstance(confirmModal);
                modal.show();
                return;
            } else {
                const href = trigger.getAttribute("href");
                e.preventDefault();
                pendingSubmit = function () {
                    window.location.href = href;
                };
                const modal = bootstrap.Modal.getOrCreateInstance(confirmModal);
                modal.show();
                return;
            }
        });

        doc("#btnKonfirmasiYa").addEventListener("click", function () {
            if (pendingSubmit) {
                if (typeof pendingSubmit === "function") pendingSubmit();
                else pendingSubmit.submit();
            }
            if (confirmModal) {
                const inst = bootstrap.Modal.getInstance(confirmModal);
                if (inst) inst.hide();
            }
        });
    });

    /* ---------- PIN modal (config modal, no faked endpoint) ---------- */
    onReady(function () {
        const pinModal = doc("#modalPin");
        if (!pinModal) return;

        let pinApproved = false;
        const pinInput = doc("#pinVerifikasi");
        if (pinInput) {
            pinInput.addEventListener("input", function () {
                const valid =
                    location.pathname.indexOf("reset-pin") > -1
                        ? pinInput.value.length >= 6
                        : /^\d{6}$/.test(pinInput.value.trim());
                pinApproved = valid;
                doc("#btnPinLanjut").disabled = !valid;
            });
        }
    });

    /* ---------- Live table filter (search + filter inputs) ---------- */
    onReady(function () {
        all("input[data-table-filter], select[data-table-filter]").forEach(
            function (input) {
                input.tagName === "INPUT"
                    ? input.addEventListener(
                          "input",
                          debounce(function () {
                              filterTable();
                          }, 250),
                      )
                    : input.addEventListener("change", filterTable);
            },
        );

        function filterTable() {
            all("table[data-filterable]").forEach(function (table) {
                const q = (doc("input[data-table-filter]") || {}).value || "";
                const type =
                    (doc("select[data-table-filter]") || {}).value || "";
                const needle = q.toLowerCase();
                Array.prototype.forEach.call(
                    table.querySelectorAll("tbody tr"),
                    function (row) {
                        let show = true;
                        if (
                            needle &&
                            row.getAttribute("data-search") !== null
                        ) {
                            show =
                                show &&
                                (row.getAttribute("data-search") || "")
                                    .toLowerCase()
                                    .indexOf(needle) > -1;
                        }
                        if (type) {
                            const rowType =
                                row.getAttribute("data-jenis") ||
                                row.getAttribute("data-status") ||
                                "";
                            show = show && rowType === type;
                        }
                        row.style.display = show ? "" : "none";
                    },
                );
            });
        }
    });

    /* ---------- Auto-dismiss contextual alerts ---------- */
    onReady(function () {
        all(".alert.auto-dismiss").forEach(function (alertEl) {
            setTimeout(function () {
                alertEl.style.transition = "opacity 0.4s ease";
                alertEl.style.opacity = "0";
                setTimeout(function () {
                    alertEl.remove();
                }, 450);
            }, 5000);
        });
    });

    /* ---------- Print struk ---------- */
    window.printStruk = function () {
        const area = doc("#areaStrukPrint");
        if (!area) return false;
        const original = document.body.innerHTML;
        document.body.innerHTML = area.outerHTML;
        window.print();
        document.body.innerHTML = original;
        return true;
    };

    window.showToast = showToast;
    window.BankMini = { showToast: showToast, debounce: debounce };
})();

/* ============================================================
   INTERAKSI TAMPILAN NASABAH
   Semua fitur bersifat progresif: halaman tetap berfungsi
   tanpa JavaScript.
   ============================================================ */
(function () {
    "use strict";

    function ready(fn) {
        if (document.readyState !== "loading") fn();
        else document.addEventListener("DOMContentLoaded", fn);
    }

    function debounce(fn, wait) {
        let timer;
        return function () {
            const ctx = this;
            const args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(ctx, args);
            }, wait);
        };
    }

    /* ---------- Toggle sembunyikan/tampilkan saldo ---------- */
    (function () {
        const KEY = "bankmini.nasabah.saldoHidden";

        function tersembunyi() {
            try {
                return localStorage.getItem(KEY) === "1";
            } catch (e) {
                return false;
            }
        }

        function simpan(nilai) {
            try {
                localStorage.setItem(KEY, nilai ? "1" : "0");
            } catch (e) {
                /* storage tidak tersedia */
            }
        }

        function terapkan() {
            const hidden = tersembunyi();

            document.querySelectorAll("[data-nilai-saldo]").forEach(function (el) {
                el.textContent = hidden
                    ? "\u2022\u2022\u2022\u2022\u2022\u2022"
                    : el.getAttribute("data-nilai-saldo");
            });

            document.querySelectorAll("[data-toggle-saldo]").forEach(function (btn) {
                const icon = btn.querySelector("i");
                if (icon) {
                    icon.className =
                        "fa-solid " + (hidden ? "fa-eye-slash" : "fa-eye");
                }
                btn.setAttribute("aria-pressed", hidden ? "true" : "false");
                const label = hidden ? "Tampilkan saldo" : "Sembunyikan saldo";
                btn.setAttribute("aria-label", label);
                btn.setAttribute("title", label);
            });
        }

        ready(function () {
            if (!document.querySelector("[data-toggle-saldo]")) return;
            terapkan();

            document.addEventListener("click", function (e) {
                const btn = e.target.closest("[data-toggle-saldo]");
                if (!btn) return;
                e.preventDefault();
                simpan(!tersembunyi());
                terapkan();
            });
        });
    })();

    /* ---------- Filter pencarian daftar riwayat ---------- */
    (function () {
        ready(function () {
            const input = document.getElementById("nCariRiwayat");
            const area = document.getElementById("nRiwayatArea");
            const kosong = document.getElementById("nCariKosong");
            if (!input || !area) return;

            const baris = Array.prototype.slice.call(
                area.querySelectorAll("[data-n-row]"),
            );

            const jalankan = debounce(function () {
                const q = (input.value || "").trim().toLowerCase();
                let tampil = 0;

                baris.forEach(function (row) {
                    const teks = (row.getAttribute("data-search") || "").toLowerCase();
                    const cocok = q === "" || teks.indexOf(q) > -1;
                    row.classList.toggle("is-hidden", !cocok);
                    if (cocok) tampil++;
                });

                if (kosong) {
                    kosong.classList.toggle(
                        "d-none",
                        !(q !== "" && tampil === 0),
                    );
                }
            }, 180);

            input.addEventListener("input", jalankan);
        });
    })();

    /* ---------- Filter tab notifikasi (semua / belum dibaca) ---------- */
    (function () {
        ready(function () {
            const tombol = Array.prototype.slice.call(
                document.querySelectorAll("[data-n-filter-notif]"),
            );
            const item = Array.prototype.slice.call(
                document.querySelectorAll("[data-n-notif]"),
            );
            if (!tombol.length || !item.length) return;

            tombol.forEach(function (btn) {
                btn.addEventListener("click", function () {
                    const mode = btn.getAttribute("data-n-filter-notif");

                    tombol.forEach(function (b) {
                        const aktif = b === btn;
                        b.classList.toggle("active", aktif);
                        b.setAttribute("aria-selected", aktif ? "true" : "false");
                    });

                    item.forEach(function (el) {
                        const belum = el.getAttribute("data-n-unread") === "1";
                        el.classList.toggle(
                            "is-hidden",
                            mode === "belum" && !belum,
                        );
                    });
                });
            });
        });
    })();

    /* ---------- Pencarian dan periode notifikasi sederhana ---------- */
    (function () {
        ready(function () {
            const items = Array.prototype.slice.call(document.querySelectorAll("[data-n-simple-item]"));
            const search = document.querySelector("[data-n-simple-search]");
            const period = document.querySelector("[data-n-simple-period]");
            if (!items.length) return;
            let mode = "semua";

            const filter = function () {
                const query = search ? search.value.trim().toLowerCase() : "";
                const value = period ? period.value : "7";
                const days = value === "all" ? 0 : Number(value);
                const cutoff = days ? Math.floor(Date.now() / 1000) - days * 86400 : 0;
                items.forEach(function (item) {
                    const text = (item.getAttribute("data-n-search") || "").toLowerCase();
                    const timestamp = Number(item.getAttribute("data-n-time") || 0);
                    const unread = item.getAttribute("data-n-unread") === "1";
                    item.classList.toggle("is-hidden", (mode === "belum" && !unread) || (query && text.indexOf(query) === -1) || (cutoff && timestamp < cutoff));
                });
            };

            if (search) search.addEventListener("input", filter);
            if (period) period.addEventListener("change", filter);
            document.querySelectorAll("[data-n-filter-notif]").forEach(function (button) {
                button.addEventListener("click", function () {
                    mode = button.getAttribute("data-n-filter-notif") || "semua";
                    filter();
                });
            });
        });
    })();

    /* ---------- Input PIN: hanya angka, maksimal 6 digit ---------- */
    (function () {
        ready(function () {
            document.querySelectorAll("input[data-pin]").forEach(function (input) {
                input.addEventListener("input", function () {
                    const bersih = input.value.replace(/\D/g, "").slice(0, 6);
                    if (bersih !== input.value) input.value = bersih;
                });
            });
        });
    })();

    /* ---------- Konfirmasi aksi (pakai modal yang sudah ada) ---------- */
    (function () {
        ready(function () {
            const modalEl = document.getElementById("modalKonfirmasi");
            if (!modalEl) return;

            let formTertunda = null;
            let tombolTertunda = null;

            document.addEventListener("click", function (e) {
                const btn = e.target.closest("[data-n-konfirmasi]");
                if (!btn) return;

                const form = btn.closest("form");
                if (!form) return;

                e.preventDefault();

                if (typeof form.checkValidity === "function" && !form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                formTertunda = form;
                tombolTertunda = btn;

                const judul =
                    btn.getAttribute("data-n-judul") || "Konfirmasi Tindakan";
                const pesan =
                    btn.getAttribute("data-n-konfirmasi") ||
                    "Apakah Anda yakin ingin melanjutkan tindakan ini?";

                const judulEl = document.getElementById("konfirmasiJudul");
                const pesanEl = document.getElementById("konfirmasiPesan");
                if (judulEl) judulEl.textContent = judul;
                if (pesanEl) pesanEl.textContent = pesan;

                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            });

            const ya = document.getElementById("btnKonfirmasiYa");
            if (ya) {
                ya.addEventListener("click", function () {
                    const form = formTertunda;
                    const btn = tombolTertunda;
                    formTertunda = null;
                    tombolTertunda = null;

                    if (!form) return;
                    if (typeof form.requestSubmit === "function") {
                        form.requestSubmit(btn || undefined);
                    } else {
                        form.submit();
                    }
                });
            }

            modalEl.addEventListener("hidden.bs.modal", function () {
                formTertunda = null;
                tombolTertunda = null;
            });
        });
    })();

    /* ---------- Baris transaksi dapat diklik menuju struk ---------- */
    (function () {
        ready(function () {
            document.querySelectorAll("[data-n-href]").forEach(function (row) {
                row.addEventListener("click", function (e) {
                    if (
                        e.target.closest(
                            "a, button, input, select, textarea, label, form",
                        )
                    ) {
                        return;
                    }
                    const href = row.getAttribute("data-n-href");
                    if (href) window.location.href = href;
                });
            });
        });
    })();
})();
